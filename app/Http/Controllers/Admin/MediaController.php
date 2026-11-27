<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisMedia;
use App\Layanan\LayananMedia;
use App\Models\BerkasMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends AdminController
{
    public function index(Request $request, LayananMedia $layanan): Response
    {
        $p = $this->parameterDaftar($request, ['dibuat_pada', 'judul', 'ukuran_bita'], 'dibuat_pada', 'desc');
        $jenis = in_array($request->query('jenis'), ['gambar', 'pdf', 'video_eksternal'], true) ? $request->query('jenis') : null;

        return Inertia::render('admin/media/index', [
            'media' => BerkasMedia::query()
                ->when($p['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('judul', 'like', self::pola($p['q']))->orWhere('nama_asli', 'like', self::pola($p['q']))))
                ->when($jenis, fn ($q) => $q->where('jenis_media', $jenis))
                ->orderBy($p['urut'], $p['arah'])
                ->paginate($p['per'] === 15 ? 24 : $p['per'])->withQueryString()
                ->through(fn (BerkasMedia $m) => $this->ringkas($m)),
            'filter' => $p + ['jenis' => $jenis],
            'batas_mb' => $layanan->batasMb(),
            'total_ukuran' => $layanan->totalUkuran(),
        ]);
    }

    /** JSON untuk pemilih media di formulir (hanya admin). */
    public function pilih(Request $request): JsonResponse
    {
        $jenis = in_array($request->query('jenis'), ['gambar', 'pdf', 'video_eksternal'], true) ? $request->query('jenis') : null;
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $hasil = BerkasMedia::query()
            ->when($jenis, fn ($w) => $w->where('jenis_media', $jenis))
            ->when($q !== '', fn ($w) => $w->where('judul', 'like', self::pola($q)))
            ->orderByDesc('dibuat_pada')->paginate(18);

        return response()->json([
            'data' => collect($hasil->items())->map(fn ($m) => $this->ringkas($m))->all(),
            'halaman' => $hasil->currentPage(),
            'halaman_terakhir' => $hasil->lastPage(),
        ]);
    }

    public function store(Request $request, LayananMedia $layanan): RedirectResponse|JsonResponse
    {
        $kb = $layanan->batasMb() * 1024;
        $data = $request->validate([
            'jenis_media' => ['required', Rule::in(['gambar', 'pdf', 'video_eksternal'])],
            'berkas' => ['required_unless:jenis_media,video_eksternal', 'nullable', 'file', 'max:'.$kb],
            'url_eksternal' => ['required_if:jenis_media,video_eksternal', 'nullable', 'string', 'max:500', 'url:https'],
            'judul' => ['nullable', 'string', 'max:180'],
            'teks_alternatif' => ['nullable', 'required_if:jenis_media,gambar', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'sumber' => ['nullable', 'string', 'max:255'],
            'lisensi' => ['nullable', 'string', 'max:120'],
        ], ['teks_alternatif.required_if' => 'Teks alternatif wajib diisi untuk gambar (aksesibilitas).', 'berkas.max' => 'Ukuran berkas melebihi batas '.$layanan->batasMb().' MB.'], ['berkas' => 'berkas', 'url_eksternal' => 'URL video']);

        $media = match ($data['jenis_media']) {
            'gambar' => $layanan->unggahGambar($request->file('berkas'), $data),
            'pdf' => $layanan->unggahPdf($request->file('berkas'), $data),
            default => $layanan->buatVideoEksternal($data['url_eksternal'], $data),
        };
        $this->audit->catat('unggah_media', 'berkas_media', $media->id, "Tambah media {$media->judul}", ['jenis_media' => $media->jenis_media->value, 'ukuran_bita' => $media->ukuran_bita]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['media' => $this->ringkas($media)], 201);
        }

        return $this->sukses('Media ditambahkan.');
    }

    public function show(BerkasMedia $media, LayananMedia $layanan): Response
    {
        return Inertia::render('admin/media/show', [
            'media' => $this->ringkas($media) + [
                'keterangan' => $media->keterangan, 'sumber' => $media->sumber, 'lisensi' => $media->lisensi,
                'nama_asli' => $media->nama_asli, 'url_eksternal' => $media->url_eksternal,
                'url_embed' => $media->jenis_media === JenisMedia::VideoEksternal ? LayananMedia::urlEmbedVideo($media->url_eksternal) : null,
                'url_publik' => $media->jenis_media === JenisMedia::Gambar && $layanan->bolehPublik($media) ? $media->urlPublik() : null,
                'checksum' => $media->checksum_sha256 ? substr($media->checksum_sha256, 0, 16).'…' : null,
            ],
            'penggunaan' => $layanan->penggunaan($media),
        ]);
    }

    public function update(Request $request, BerkasMedia $media): RedirectResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'teks_alternatif' => [$media->jenis_media === JenisMedia::Gambar ? 'required' : 'nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'sumber' => ['nullable', 'string', 'max:255'],
            'lisensi' => ['nullable', 'string', 'max:120'],
        ]);
        $media->fill($data)->save();
        $this->audit->catatModel('perbarui', $media, "Perbarui metadata media {$media->judul}");

        return $this->sukses('Metadata media disimpan.');
    }

    public function destroy(BerkasMedia $media, LayananMedia $layanan): RedirectResponse
    {
        $judul = $media->judul;
        $id = $media->id;
        $layanan->hapus($media);
        $this->audit->catat('hapus', 'berkas_media', $id, "Hapus media {$judul}");

        return $this->sukses('Media dihapus.', '/admin/media');
    }

    public function berkas(BerkasMedia $media): BinaryFileResponse
    {
        abort_if($media->jenis_media === JenisMedia::VideoEksternal || ! $media->jalur_berkas, 404);
        $disk = Storage::disk($media->lokasi_penyimpanan ?: LayananMedia::DISK);
        abort_unless($disk->exists($media->jalur_berkas), 404);

        $response = new BinaryFileResponse($disk->path($media->jalur_berkas), 200, [
            'Content-Type' => $media->tipe_mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ], false, 'inline', true, true);

        return $response;
    }

    private function ringkas(BerkasMedia $m): array
    {
        return [
            'id' => $m->id, 'uuid' => $m->uuid, 'judul' => $m->judul, 'jenis' => $m->jenis_media->value,
            'url' => $m->urlAdmin(), 'ekstensi' => $m->ekstensi, 'tipe_mime' => $m->tipe_mime, 'ukuran_bita' => $m->ukuran_bita,
            'lebar' => $m->lebar_piksel, 'tinggi' => $m->tinggi_piksel, 'jumlah_halaman' => $m->jumlah_halaman,
            'teks_alternatif' => $m->teks_alternatif, 'dibuat_pada' => $m->dibuat_pada?->toIso8601String(),
        ];
    }
}
