<?php

namespace App\Layanan;

use App\Enums\StatusKonten;
use App\Models\BabModul;
use App\Models\Pengguna;
use App\Models\VersiDokumen;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

/**
 * Versi dokumen PDF: pembuatan draf, aktivasi transaksional (satu versi aktif per lingkup),
 * pengarsipan, dan penyajian berkas privat dengan header aman.
 */
class LayananDokumen
{
    public function __construct(
        private readonly LayananMedia $media,
        private readonly LayananAudit $audit,
        private readonly LayananPengaturan $pengaturan,
        private readonly LayananStatistik $statistik,
    ) {}

    /** @param array{nomor_versi: string, judul_dokumen: string, catatan_perubahan?: ?string, izinkan_unduh?: bool} $data */
    public function buatDraf(string $jenis, ?BabModul $bab, UploadedFile $pdf, array $data): VersiDokumen
    {
        $this->pastikanNomorVersiUnik($jenis, $bab?->id, $data['nomor_versi']);
        $berkas = $this->media->unggahPdf($pdf, ['judul' => $data['judul_dokumen']], 'berkas');

        try {
            return DB::transaction(function () use ($jenis, $bab, $berkas, $data) {
                $versi = new VersiDokumen;
                $versi->forceFill([
                    'jenis_dokumen' => $jenis,
                    'bab_id' => $jenis === 'bab' ? $bab?->id : null,
                    'berkas_id' => $berkas->id,
                    'nomor_versi' => $data['nomor_versi'],
                    'judul_dokumen' => $data['judul_dokumen'],
                    'catatan_perubahan' => $data['catatan_perubahan'] ?? null,
                    'izinkan_unduh' => (bool) ($data['izinkan_unduh'] ?? true),
                    'status' => StatusKonten::Draf,
                    'versi_aktif' => false,
                    'dibuat_oleh' => auth()->id(),
                    'diperbarui_oleh' => auth()->id(),
                ])->save();
                $this->audit->catat('unggah_dokumen', 'versi_dokumen', $versi->id, "Unggah draf {$versi->judul_dokumen} v{$versi->nomor_versi}", [
                    'jenis_dokumen' => $jenis, 'bab_id' => $versi->bab_id, 'ukuran_bita' => $berkas->ukuran_bita, 'jumlah_halaman' => $berkas->jumlah_halaman,
                ]);

                return $versi;
            });
        } catch (Throwable $e) {
            // Rekaman versi gagal: hapus media yang baru diunggah agar tidak menjadi berkas yatim.
            $this->media->hapus($berkas);
            throw $e;
        }
    }

    /**
     * Aktifkan dan terbitkan versi. Versi aktif sebelumnya pada lingkup yang sama diarsipkan.
     * Dijamin satu versi aktif per lingkup melalui row lock + indeks unik `kunci_versi_aktif`.
     */
    public function aktifkan(VersiDokumen $versi, ?Pengguna $oleh = null): VersiDokumen
    {
        try {
            return DB::transaction(function () use ($versi, $oleh) {
                /** @var VersiDokumen $terkunci */
                $terkunci = VersiDokumen::query()->with('berkas')->lockForUpdate()->findOrFail($versi->id);
                // Kunci seluruh versi pada lingkup yang sama (serialisasi aktivasi serentak).
                $lingkup = VersiDokumen::query()
                    ->where('jenis_dokumen', $terkunci->jenis_dokumen)
                    ->when($terkunci->bab_id, fn ($q) => $q->where('bab_id', $terkunci->bab_id), fn ($q) => $q->whereNull('bab_id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($terkunci->status === StatusKonten::Arsip && ! $terkunci->berkas) {
                    throw ValidationException::withMessages(['versi' => 'Versi ini tidak memiliki berkas.']);
                }
                $berkas = $terkunci->berkas;
                if (! $berkas || ! Storage::disk($berkas->lokasi_penyimpanan ?: LayananMedia::DISK)->exists((string) $berkas->jalur_berkas)) {
                    throw ValidationException::withMessages(['versi' => 'Berkas PDF versi ini tidak ditemukan di penyimpanan. Unggah ulang sebelum menerbitkan.']);
                }

                $sebelumnya = $lingkup->firstWhere('versi_aktif', true);
                foreach ($lingkup as $lain) {
                    if ($lain->id !== $terkunci->id && $lain->versi_aktif) {
                        $lain->forceFill(['versi_aktif' => false, 'status' => StatusKonten::Arsip, 'diperbarui_oleh' => $oleh?->id])->save();
                    }
                }

                $terkunci->forceFill([
                    'status' => StatusKonten::Terbit,
                    'versi_aktif' => true,
                    'diterbitkan_pada' => $terkunci->diterbitkan_pada ?? now(),
                    'diterbitkan_oleh' => $oleh?->id,
                    'diperbarui_oleh' => $oleh?->id,
                ])->save();

                $this->audit->catat('terbitkan_dokumen', 'versi_dokumen', $terkunci->id, "Terbitkan {$terkunci->judul_dokumen} v{$terkunci->nomor_versi}", [
                    'versi_sebelumnya' => $sebelumnya && $sebelumnya->id !== $terkunci->id ? $sebelumnya->nomor_versi : null,
                ], $oleh?->id);
                $this->pengaturan->naikkanVersiKonten();

                return $terkunci;
            }, 3);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'versi_dokumen_satu_aktif_unique')) {
                throw ValidationException::withMessages(['versi' => 'Versi lain sedang diaktifkan bersamaan. Muat ulang halaman lalu coba lagi.']);
            }
            throw $e;
        }
    }

    public function arsipkan(VersiDokumen $versi): void
    {
        DB::transaction(function () use ($versi) {
            $versi->forceFill(['status' => StatusKonten::Arsip, 'versi_aktif' => false, 'diperbarui_oleh' => auth()->id()])->save();
            $this->audit->catat('arsipkan_dokumen', 'versi_dokumen', $versi->id, "Arsipkan {$versi->judul_dokumen} v{$versi->nomor_versi}");
        });
        $this->pengaturan->naikkanVersiKonten();
    }

    /** Hanya versi draf yang belum pernah terbit yang boleh dihapus permanen. */
    public function hapusDraf(VersiDokumen $versi): void
    {
        if ($versi->status !== StatusKonten::Draf || $versi->diterbitkan_pada) {
            throw ValidationException::withMessages(['versi' => 'Hanya versi draf yang belum pernah terbit yang dapat dihapus. Gunakan arsip untuk versi lain.']);
        }
        $berkas = $versi->berkas;
        DB::transaction(function () use ($versi) {
            $this->audit->catat('hapus_dokumen', 'versi_dokumen', $versi->id, "Hapus draf {$versi->judul_dokumen} v{$versi->nomor_versi}");
            $versi->delete();
        });
        if ($berkas && $this->media->penggunaan($berkas) === []) {
            $this->media->hapus($berkas);
        }
    }

    public function izinUnduhEfektif(VersiDokumen $versi): bool
    {
        return (bool) $this->pengaturan->ambil()->izinkan_unduh && $versi->izinkan_unduh;
    }

    /** Ambil versi yang boleh disajikan publik atau 404. */
    public function versiPublik(int $id): VersiDokumen
    {
        return VersiDokumen::query()->tersediaPublik()->with(['berkas', 'bab'])->findOrFail($id);
    }

    /**
     * Sajikan berkas PDF. Mendukung HTTP Range (206) via BinaryFileResponse.
     * Statistik: hanya permintaan tanpa Range atau Range yang dimulai dari byte 0 yang dicatat,
     * lalu dideduplikasi per pengunjung/versi/jenis/hari.
     */
    public function sajikan(VersiDokumen $versi, Request $request, string $mode, bool $publik = true): BinaryFileResponse
    {
        $berkas = $versi->berkas;
        $disk = Storage::disk($berkas->lokasi_penyimpanan ?: LayananMedia::DISK);
        $jalur = (string) $berkas->jalur_berkas;
        abort_unless($jalur !== '' && $disk->exists($jalur), 404);

        if ($publik && $this->permintaanAwal($request)) {
            $this->statistik->catatAktivitasDokumen($request, $versi, $mode === 'unduh' ? 'unduh' : 'baca');
        }

        $response = new BinaryFileResponse($disk->path($jalur), 200, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ], true, null, true, true);
        $response->setContentDisposition(
            $mode === 'unduh' ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $versi->namaBerkasAman(),
        );
        if ($publik) {
            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-cache');
        } else {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        }

        return $response;
    }

    private function permintaanAwal(Request $request): bool
    {
        if (! in_array($request->method(), ['GET'], true)) {
            return false;
        }
        $range = (string) $request->headers->get('Range', '');

        return $range === '' || (bool) preg_match('/^bytes=0-/', $range);
    }

    private function pastikanNomorVersiUnik(string $jenis, ?int $babId, string $nomor): void
    {
        $ada = VersiDokumen::query()->where('jenis_dokumen', $jenis)
            ->when($babId, fn ($q) => $q->where('bab_id', $babId), fn ($q) => $q->whereNull('bab_id'))
            ->where('nomor_versi', $nomor)->exists();
        if ($ada) {
            throw ValidationException::withMessages(['nomor_versi' => 'Nomor versi ini sudah dipakai pada dokumen yang sama.']);
        }
    }
}
