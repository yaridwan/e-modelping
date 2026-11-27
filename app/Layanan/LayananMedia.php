<?php

namespace App\Layanan;

use App\Enums\JenisMedia;
use App\Enums\StatusKonten;
use App\Models\BabModul;
use App\Models\BagianBeranda;
use App\Models\BerkasMedia;
use App\Models\FasePing;
use App\Models\GaleriKegiatan;
use App\Models\HalamanInformasi;
use App\Models\LangkahFase;
use App\Models\PengaturanWebsite;
use App\Models\Pengguna;
use App\Models\SubbabModul;
use App\Models\VersiDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Pustaka media: validasi unggahan berbasis isi (bukan sekadar ekstensi), penyimpanan privat
 * dengan nama buatan aplikasi, pemeriksaan penggunaan sebelum hapus, dan otorisasi akses publik.
 *
 * Catatan: validasi MIME/signature/parser bukan pemindai malware. Lihat README bagian keamanan.
 */
class LayananMedia
{
    public const DISK = 'local';

    public function __construct(
        private readonly LayananPengaturan $pengaturan,
    ) {}

    public function batasBita(): int
    {
        $maks = (int) config('ping.batas_unggah_maksimum_mb', 25);
        $mb = min($maks, max(1, (int) ($this->pengaturan->ambil()->batas_unggah_mb ?: $maks)));

        return $mb * 1024 * 1024;
    }

    public function batasMb(): int
    {
        return intdiv($this->batasBita(), 1024 * 1024);
    }

    /** @param array{judul?: string, teks_alternatif?: string, keterangan?: string, sumber?: string, lisensi?: string} $meta */
    public function unggahGambar(UploadedFile $berkas, array $meta = [], string $kolom = 'berkas'): BerkasMedia
    {
        $this->periksaUkuran($berkas, $kolom);
        $mime = $this->mimeServer($berkas);
        $diizinkan = (array) config('ping.gambar.mime_diizinkan');
        if (! in_array($mime, $diizinkan, true)) {
            throw ValidationException::withMessages([$kolom => 'Format gambar tidak diizinkan. Gunakan JPG, PNG, atau WebP. SVG unggahan tidak diterima demi keamanan.']);
        }

        $info = @getimagesize($berkas->getRealPath());
        if (! $info || $info[0] < 1 || $info[1] < 1) {
            throw ValidationException::withMessages([$kolom => 'Berkas gambar rusak atau tidak dapat dibaca.']);
        }
        if ($info[0] * $info[1] > 40_000_000) {
            throw ValidationException::withMessages([$kolom => 'Resolusi gambar terlalu besar (maksimal 40 megapiksel).']);
        }

        [$biner, $ekstensi, $lebar, $tinggi] = $this->prosesUlangGambar($berkas->getRealPath(), $mime);
        $uuid = (string) Str::uuid();
        $jalur = 'media/gambar/'.now()->format('Y/m').'/'.$uuid.'.'.$ekstensi;

        return $this->simpanAtomik($jalur, $biner, function () use ($uuid, $jalur, $berkas, $meta, $ekstensi, $lebar, $tinggi, $biner) {
            $media = new BerkasMedia;
            $media->forceFill([
                'uuid' => $uuid,
                'judul' => Str::limit($meta['judul'] ?? pathinfo($berkas->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Gambar', 180, ''),
                'jenis_media' => JenisMedia::Gambar,
                'nama_asli' => Str::limit($berkas->getClientOriginalName(), 250, ''),
                'nama_simpan' => basename($jalur),
                'lokasi_penyimpanan' => self::DISK,
                'jalur_berkas' => $jalur,
                'tipe_mime' => $ekstensi === 'png' ? 'image/png' : ($ekstensi === 'webp' ? 'image/webp' : 'image/jpeg'),
                'ekstensi' => $ekstensi,
                'ukuran_bita' => strlen($biner),
                'lebar_piksel' => $lebar,
                'tinggi_piksel' => $tinggi,
                'teks_alternatif' => $meta['teks_alternatif'] ?? null,
                'keterangan' => $meta['keterangan'] ?? null,
                'sumber' => $meta['sumber'] ?? null,
                'lisensi' => $meta['lisensi'] ?? null,
                'checksum_sha256' => hash('sha256', $biner),
                'diunggah_oleh' => auth()->id(),
            ])->save();

            return $media;
        });
    }

    public function unggahPdf(UploadedFile $berkas, array $meta = [], string $kolom = 'berkas'): BerkasMedia
    {
        $this->periksaUkuran($berkas, $kolom);
        $jalurSementara = $berkas->getRealPath();
        $jumlahHalaman = $this->validasiPdf($jalurSementara, $berkas->getClientOriginalExtension(), $kolom);
        $biner = (string) file_get_contents($jalurSementara);
        $uuid = (string) Str::uuid();
        $jalur = 'media/pdf/'.now()->format('Y/m').'/'.$uuid.'.pdf';

        return $this->simpanAtomik($jalur, $biner, function () use ($uuid, $jalur, $berkas, $meta, $biner, $jumlahHalaman) {
            $media = new BerkasMedia;
            $media->forceFill([
                'uuid' => $uuid,
                'judul' => Str::limit($meta['judul'] ?? pathinfo($berkas->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Dokumen PDF', 180, ''),
                'jenis_media' => JenisMedia::Pdf,
                'nama_asli' => Str::limit($berkas->getClientOriginalName(), 250, ''),
                'nama_simpan' => basename($jalur),
                'lokasi_penyimpanan' => self::DISK,
                'jalur_berkas' => $jalur,
                'tipe_mime' => 'application/pdf',
                'ekstensi' => 'pdf',
                'ukuran_bita' => strlen($biner),
                'jumlah_halaman' => $jumlahHalaman,
                'keterangan' => $meta['keterangan'] ?? null,
                'sumber' => $meta['sumber'] ?? null,
                'lisensi' => $meta['lisensi'] ?? null,
                'checksum_sha256' => hash('sha256', $biner),
                'diunggah_oleh' => auth()->id(),
            ])->save();

            return $media;
        });
    }

    /** Impor PDF dari jalur lokal tepercaya (seeder). */
    public function imporPdfLokal(string $jalurSumber, string $judul, array $meta = []): BerkasMedia
    {
        $jumlahHalaman = $this->validasiPdf($jalurSumber, 'pdf', 'berkas');
        $biner = (string) file_get_contents($jalurSumber);
        $checksum = hash('sha256', $biner);
        $ada = BerkasMedia::query()->where('checksum_sha256', $checksum)->where('jenis_media', JenisMedia::Pdf)->first();
        if ($ada && Storage::disk(self::DISK)->exists((string) $ada->jalur_berkas)) {
            return $ada;
        }
        $uuid = (string) Str::uuid();
        $jalur = 'media/pdf/sumber/'.$uuid.'.pdf';

        return $this->simpanAtomik($jalur, $biner, function () use ($uuid, $jalur, $jalurSumber, $judul, $meta, $biner, $checksum, $jumlahHalaman) {
            $media = new BerkasMedia;
            $media->forceFill([
                'uuid' => $uuid, 'judul' => $judul, 'jenis_media' => JenisMedia::Pdf,
                'nama_asli' => basename($jalurSumber), 'nama_simpan' => basename($jalur), 'lokasi_penyimpanan' => self::DISK,
                'jalur_berkas' => $jalur, 'tipe_mime' => 'application/pdf', 'ekstensi' => 'pdf', 'ukuran_bita' => strlen($biner),
                'jumlah_halaman' => $jumlahHalaman, 'keterangan' => $meta['keterangan'] ?? null, 'sumber' => $meta['sumber'] ?? null,
                'lisensi' => $meta['lisensi'] ?? null, 'checksum_sha256' => $checksum,
            ])->save();

            return $media;
        });
    }

    /** Impor gambar dari jalur lokal tepercaya (seeder / aset bawaan). */
    public function imporGambarLokal(string $jalurSumber, string $judul, string $teksAlternatif, array $meta = []): BerkasMedia
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($jalurSumber) ?: '';
        [$biner, $ekstensi, $lebar, $tinggi] = $this->prosesUlangGambar($jalurSumber, $mime);
        $uuid = (string) Str::uuid();
        $jalur = 'media/gambar/bawaan/'.$uuid.'.'.$ekstensi;

        return $this->simpanAtomik($jalur, $biner, function () use ($uuid, $jalur, $jalurSumber, $judul, $teksAlternatif, $meta, $biner, $ekstensi, $lebar, $tinggi) {
            $media = new BerkasMedia;
            $media->forceFill([
                'uuid' => $uuid, 'judul' => $judul, 'jenis_media' => JenisMedia::Gambar, 'nama_asli' => basename($jalurSumber),
                'nama_simpan' => basename($jalur), 'lokasi_penyimpanan' => self::DISK, 'jalur_berkas' => $jalur,
                'tipe_mime' => $ekstensi === 'png' ? 'image/png' : ($ekstensi === 'webp' ? 'image/webp' : 'image/jpeg'),
                'ekstensi' => $ekstensi, 'ukuran_bita' => strlen($biner), 'lebar_piksel' => $lebar, 'tinggi_piksel' => $tinggi,
                'teks_alternatif' => $teksAlternatif, 'keterangan' => $meta['keterangan'] ?? null, 'sumber' => $meta['sumber'] ?? null,
                'lisensi' => $meta['lisensi'] ?? null, 'checksum_sha256' => hash('sha256', $biner),
            ])->save();

            return $media;
        });
    }

    public function buatVideoEksternal(string $url, array $meta = [], string $kolom = 'url_eksternal'): BerkasMedia
    {
        $embed = self::urlEmbedVideo($url);
        if (! $embed) {
            throw ValidationException::withMessages([$kolom => 'Tautan video harus berasal dari YouTube atau Vimeo dengan format yang valid.']);
        }
        $media = new BerkasMedia;
        $media->forceFill([
            'uuid' => (string) Str::uuid(),
            'judul' => Str::limit($meta['judul'] ?? 'Video pendukung', 180, ''),
            'jenis_media' => JenisMedia::VideoEksternal,
            'url_eksternal' => $url,
            'ukuran_bita' => 0,
            'teks_alternatif' => $meta['teks_alternatif'] ?? null,
            'keterangan' => $meta['keterangan'] ?? null,
            'sumber' => $meta['sumber'] ?? null,
            'lisensi' => $meta['lisensi'] ?? null,
            'diunggah_oleh' => auth()->id(),
        ])->save();

        return $media;
    }

    /** URL embed aman (youtube-nocookie / player.vimeo) tanpa melakukan fetch ke server pihak ketiga. */
    public static function urlEmbedVideo(?string $url): ?string
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $bagian = parse_url($url);
        $host = strtolower($bagian['host'] ?? '');
        if (($bagian['scheme'] ?? '') !== 'https' || ! in_array($host, (array) config('ping.video_diizinkan'), true)) {
            return null;
        }
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str($bagian['query'] ?? '', $q);
            $id = $q['v'] ?? (preg_match('#^/(?:embed|shorts)/([A-Za-z0-9_-]{11})#', $bagian['path'] ?? '', $m) ? $m[1] : null);
        } elseif ($host === 'youtu.be') {
            $id = ltrim($bagian['path'] ?? '', '/');
        } else {
            $id = preg_match('#/(?:video/)?(\d{6,12})#', $bagian['path'] ?? '', $m) ? $m[1] : null;

            return $id ? 'https://player.vimeo.com/video/'.$id.'?dnt=1' : null;
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? 'https://www.youtube-nocookie.com/embed/'.$id : null;
    }

    /**
     * Daftar tempat media dipakai. Dipakai untuk mencegah penghapusan berkas yang masih dirujuk.
     *
     * @return list<array{jenis: string, label: string, tautan: ?string}>
     */
    public function penggunaan(BerkasMedia $media): array
    {
        $hasil = [];
        $tambah = function (string $jenis, string $label, ?string $tautan = null) use (&$hasil) {
            $hasil[] = compact('jenis', 'label', 'tautan');
        };

        $pengaturan = PengaturanWebsite::query()->first();
        if ($pengaturan) {
            foreach (['logo_terang_id' => 'Logo terang', 'logo_gelap_id' => 'Logo gelap', 'favicon_id' => 'Favicon', 'gambar_sampul_id' => 'Sampul bawaan', 'gambar_og_id' => 'Gambar Open Graph'] as $kolom => $label) {
                if ((int) $pengaturan->{$kolom} === $media->id) {
                    $tambah('pengaturan', "Pengaturan website: {$label}", '/admin/pengaturan');
                }
            }
        }
        foreach (Pengguna::query()->where('foto_profil_id', $media->id)->get(['id', 'nama_lengkap']) as $p) {
            $tambah('profil', 'Foto profil '.$p->nama_lengkap, '/admin/profil');
        }
        foreach (BabModul::query()->where(fn ($q) => $q->where('gambar_sampul_id', $media->id)->orWhere('gambar_og_id', $media->id)->orWhere('pengantar', 'like', '%'.$media->uuid.'%'))->get(['id', 'nomor_romawi', 'judul']) as $b) {
            $tambah('bab', "Bab {$b->nomor_romawi} {$b->judul}", "/admin/bab/{$b->id}/edit");
        }
        foreach (SubbabModul::query()->where(fn ($q) => $q->where('media_id', $media->id)->orWhere('isi', 'like', '%'.$media->uuid.'%')->orWhere('contoh_praktik', 'like', '%'.$media->uuid.'%'))->get(['id', 'bab_id', 'judul']) as $s) {
            $tambah('subbab', "Subbab {$s->judul}", "/admin/bab/{$s->bab_id}/subbab/{$s->id}/edit");
        }
        foreach (HalamanInformasi::query()->where(fn ($q) => $q->where('gambar_sampul_id', $media->id)->orWhere('gambar_og_id', $media->id)->orWhere('isi', 'like', '%'.$media->uuid.'%'))->get(['id', 'judul']) as $h) {
            $tambah('halaman', "Halaman {$h->judul}", "/admin/halaman/{$h->id}/edit");
        }
        foreach (BagianBeranda::query()->where('media_id', $media->id)->get(['id', 'judul']) as $b) {
            $tambah('beranda', "Bagian beranda {$b->judul}", '/admin/beranda');
        }
        foreach (VersiDokumen::query()->where('berkas_id', $media->id)->get(['id', 'judul_dokumen', 'nomor_versi']) as $v) {
            $tambah('dokumen', "Dokumen {$v->judul_dokumen} v{$v->nomor_versi}", '/admin/dokumen');
        }
        foreach (FasePing::query()->where('media_id', $media->id)->get(['id', 'nama']) as $f) {
            $tambah('fase', "Fase {$f->nama}", "/admin/fase/{$f->id}/edit");
        }
        foreach (LangkahFase::query()->where('media_id', $media->id)->get(['id', 'fase_id', 'judul']) as $l) {
            $tambah('langkah', "Langkah {$l->judul}", "/admin/fase/{$l->fase_id}/edit");
        }
        foreach (GaleriKegiatan::query()->where('media_id', $media->id)->get(['id', 'judul']) as $g) {
            $tambah('galeri', "Galeri {$g->judul}", "/admin/galeri/{$g->id}/edit");
        }

        return $hasil;
    }

    public function hapus(BerkasMedia $media): void
    {
        if ($this->penggunaan($media) !== []) {
            throw ValidationException::withMessages(['media' => 'Media masih digunakan. Lepaskan semua penggunaan sebelum menghapus.']);
        }
        $jalur = $media->jalur_berkas;
        DB::transaction(fn () => $media->delete());
        if ($jalur) {
            Storage::disk($media->lokasi_penyimpanan ?: self::DISK)->delete($jalur);
        }
    }

    /**
     * Gambar hanya dilayani ke publik bila dirujuk oleh konteks yang terbit/publik.
     * Hasil di-cache per versi konten.
     */
    public function bolehPublik(BerkasMedia $media): bool
    {
        if ($media->jenis_media !== JenisMedia::Gambar) {
            return false;
        }

        return (bool) $this->pengaturan->ingat('media-publik.'.$media->id, function () use ($media) {
            $id = $media->id;
            $uuid = '%'.$media->uuid.'%';
            $terbit = StatusKonten::Terbit->value;

            $pengaturan = PengaturanWebsite::query()->where(fn ($q) => $q->where('logo_terang_id', $id)->orWhere('logo_gelap_id', $id)->orWhere('favicon_id', $id)->orWhere('gambar_sampul_id', $id)->orWhere('gambar_og_id', $id))->exists();

            return $pengaturan
                || Pengguna::query()->where('foto_profil_id', $id)->where('tampilkan_profil_publik', true)->where('tampilkan_foto_publik', true)->exists()
                || BabModul::query()->where('status', $terbit)->where(fn ($q) => $q->where('gambar_sampul_id', $id)->orWhere('gambar_og_id', $id)->orWhere('pengantar', 'like', $uuid))->exists()
                || SubbabModul::query()->where('status', $terbit)->whereHas('bab', fn ($q) => $q->where('status', $terbit))->where(fn ($q) => $q->where('media_id', $id)->orWhere('isi', 'like', $uuid)->orWhere('contoh_praktik', 'like', $uuid))->exists()
                || HalamanInformasi::query()->where('status', $terbit)->where(fn ($q) => $q->where('gambar_sampul_id', $id)->orWhere('gambar_og_id', $id)->orWhere('isi', 'like', $uuid))->exists()
                || BagianBeranda::query()->where('aktif', true)->where('media_id', $id)->exists()
                || FasePing::query()->where('status', $terbit)->where('media_id', $id)->exists()
                || LangkahFase::query()->where('media_id', $id)->whereHas('fase', fn ($q) => $q->where('status', $terbit))->exists()
                || GaleriKegiatan::query()->where('status', $terbit)->where('media_id', $id)->exists();
        }, 600);
    }

    public function totalUkuran(): int
    {
        return (int) BerkasMedia::query()->sum('ukuran_bita');
    }

    // ------------------------------------------------------------------

    private function periksaUkuran(UploadedFile $berkas, string $kolom): void
    {
        if (! $berkas->isValid()) {
            throw ValidationException::withMessages([$kolom => 'Unggahan gagal. Pastikan ukuran berkas tidak melebihi batas server lalu coba lagi.']);
        }
        if ($berkas->getSize() > $this->batasBita()) {
            throw ValidationException::withMessages([$kolom => 'Ukuran berkas melebihi batas '.$this->batasMb().' MB.']);
        }
        if ($berkas->getSize() < 16) {
            throw ValidationException::withMessages([$kolom => 'Berkas kosong atau terlalu kecil.']);
        }
    }

    private function mimeServer(UploadedFile $berkas): string
    {
        return (string) (new \finfo(FILEINFO_MIME_TYPE))->file($berkas->getRealPath());
    }

    /** @return int jumlah halaman */
    public function validasiPdf(string $jalur, string $ekstensi, string $kolom): int
    {
        if (strtolower($ekstensi) !== 'pdf') {
            throw ValidationException::withMessages([$kolom => 'Ekstensi berkas harus .pdf.']);
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($jalur);
        if ($mime !== 'application/pdf') {
            throw ValidationException::withMessages([$kolom => 'Isi berkas bukan PDF (terdeteksi: '.($mime ?: 'tidak dikenal').').']);
        }
        $ukuran = (int) filesize($jalur);
        $h = fopen($jalur, 'rb');
        $awal = (string) fread($h, 1024);
        fseek($h, max(0, $ukuran - 2048));
        $akhir = (string) fread($h, 2048);
        fclose($h);
        if (! str_starts_with($awal, '%PDF-')) {
            throw ValidationException::withMessages([$kolom => 'Signature PDF tidak valid. Berkas mungkin disamarkan atau rusak.']);
        }
        if (! str_contains($akhir, '%%EOF')) {
            throw ValidationException::withMessages([$kolom => 'Struktur akhir PDF tidak lengkap. Berkas mungkin rusak atau terpotong.']);
        }
        $isi = (string) file_get_contents($jalur);
        if (preg_match('/\/Encrypt\s*(\d+\s+\d+\s+R|<<)/', $isi)) {
            throw ValidationException::withMessages([$kolom => 'PDF terenkripsi/terproteksi sandi tidak didukung penampil. Unggah PDF tanpa enkripsi.']);
        }
        if (preg_match('/\/(JavaScript|JS|Launch|EmbeddedFile)\b/', $isi)) {
            throw ValidationException::withMessages([$kolom => 'PDF memuat JavaScript, aksi Launch, atau berkas tertanam yang tidak diizinkan.']);
        }

        $batasLama = ini_get('memory_limit');
        try {
            @ini_set('memory_limit', (string) config('ping.pdf.batas_memori_parser', '256M'));
            $pdf = (new Parser)->parseContent($isi);
            $halaman = count($pdf->getPages());
        } catch (Throwable) {
            throw ValidationException::withMessages([$kolom => 'PDF tidak dapat dibaca parser. Pastikan berkas tidak rusak.']);
        } finally {
            @ini_set('memory_limit', (string) $batasLama);
        }
        if ($halaman < 1) {
            throw ValidationException::withMessages([$kolom => 'PDF tidak memiliki halaman yang dapat dibaca.']);
        }
        if ($halaman > (int) config('ping.pdf.maks_halaman', 500)) {
            throw ValidationException::withMessages([$kolom => 'Jumlah halaman PDF melebihi batas '.config('ping.pdf.maks_halaman').' halaman.']);
        }

        return $halaman;
    }

    /** @return array{0: string, 1: string, 2: int, 3: int} biner, ekstensi, lebar, tinggi */
    private function prosesUlangGambar(string $jalur, string $mime): array
    {
        $sumber = @imagecreatefromstring((string) file_get_contents($jalur));
        if (! $sumber) {
            throw ValidationException::withMessages(['berkas' => 'Gambar tidak dapat diproses.']);
        }
        $lebar = imagesx($sumber);
        $tinggi = imagesy($sumber);
        $maks = (int) config('ping.gambar.maks_piksel_sisi', 4000);
        if (max($lebar, $tinggi) > $maks) {
            $skala = $maks / max($lebar, $tinggi);
            $baru = imagescale($sumber, (int) round($lebar * $skala), (int) round($tinggi * $skala));
            imagedestroy($sumber);
            $sumber = $baru;
            $lebar = imagesx($sumber);
            $tinggi = imagesy($sumber);
        }

        ob_start();
        if ($mime === 'image/png') {
            imagealphablending($sumber, false);
            imagesavealpha($sumber, true);
            imagepng($sumber, null, 7);
            $ekstensi = 'png';
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($sumber, null, 85);
            $ekstensi = 'webp';
        } else {
            imageinterlace($sumber, true);
            imagejpeg($sumber, null, 85);
            $ekstensi = 'jpg';
        }
        $biner = (string) ob_get_clean();
        imagedestroy($sumber);

        return [$biner, $ekstensi, $lebar, $tinggi];
    }

    /**
     * Simpan berkas lalu catat ke DB; bila pencatatan gagal, berkas dihapus agar tidak yatim.
     *
     * @template T
     *
     * @param  \Closure(): T  $catat
     * @return T
     */
    private function simpanAtomik(string $jalur, string $biner, \Closure $catat): mixed
    {
        if (str_contains($jalur, '..')) {
            throw new \RuntimeException('Jalur berkas tidak valid.');
        }
        $disk = Storage::disk(self::DISK);
        if (! $disk->put($jalur, $biner)) {
            throw ValidationException::withMessages(['berkas' => 'Berkas gagal disimpan ke penyimpanan server.']);
        }
        try {
            return DB::transaction($catat);
        } catch (Throwable $e) {
            $disk->delete($jalur);
            throw $e;
        }
    }
}
