<?php

namespace App\Layanan;

use App\Enums\StatusKonten;
use App\Models\PengalihanUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Perubahan status publikasi dan slug. Perubahan slug membuat pengalihan 301 dari URL lama ke URL
 * kanonis baru, memperbarui pengalihan lama agar tidak berantai, dan mencegah loop.
 */
class LayananPublikasi
{
    public function __construct(
        private readonly LayananAudit $audit,
        private readonly LayananPengaturan $pengaturan,
    ) {}

    public function ubahStatus(Model $model, StatusKonten $status, string $label): void
    {
        DB::transaction(function () use ($model, $status, $label) {
            $lama = $model->getAttribute('status');
            $model->forceFill(['status' => $status]);
            $atribut = $model->getAttributes();
            if ($status === StatusKonten::Terbit && array_key_exists('diterbitkan_pada', $atribut) && ! $model->getAttribute('diterbitkan_pada')) {
                $model->forceFill(['diterbitkan_pada' => now()]);
            }
            if (array_key_exists('diperbarui_oleh', $atribut)) {
                $model->forceFill(['diperbarui_oleh' => auth()->id()]);
            }
            $model->save();
            $aksi = match ($status) {
                StatusKonten::Terbit => 'terbitkan',
                StatusKonten::Arsip => 'arsipkan',
                StatusKonten::Draf => 'jadikan_draf',
            };
            $this->audit->catat($aksi, $model->getTable(), (int) $model->getKey(), ucfirst(str_replace('_', ' ', $aksi)).': '.$label, [
                'status' => ['sebelum' => $lama instanceof StatusKonten ? $lama->value : $lama, 'sesudah' => $status->value],
            ]);
        });
        $this->pengaturan->naikkanVersiKonten();
    }

    /** Daftarkan pengalihan permanen jalur lama -> jalur baru (keduanya jalur internal). */
    public function catatPengalihan(string $jalurLama, string $jalurBaru): void
    {
        $jalurLama = $this->normalkan($jalurLama);
        $jalurBaru = $this->normalkan($jalurBaru);
        if ($jalurLama === $jalurBaru) {
            return;
        }

        DB::transaction(function () use ($jalurLama, $jalurBaru) {
            // Jalur baru kini konten aktif: hapus pengalihan yang berawal dari jalur baru (cegah loop).
            PengalihanUrl::query()->where('jalur_lama', $jalurBaru)->delete();
            // Ratakan rantai: semua pengalihan menuju jalur lama diarahkan langsung ke jalur baru.
            PengalihanUrl::query()->where('jalur_baru', $jalurLama)->update(['jalur_baru' => $jalurBaru, 'diperbarui_pada' => now()]);
            PengalihanUrl::query()->updateOrCreate(['jalur_lama' => $jalurLama], ['jalur_baru' => $jalurBaru, 'kode_status' => 301, 'aktif' => true]);
        });
        $this->audit->catat('buat_pengalihan', 'pengalihan_url', null, "Pengalihan 301 {$jalurLama} → {$jalurBaru}");
        $this->pengaturan->naikkanVersiKonten();
    }

    /** Validasi pengalihan manual dari admin: hanya jalur internal, tanpa loop/rantai panjang. */
    public function validasiPengalihan(string $jalurLama, string $jalurBaru, ?int $abaikanId = null): void
    {
        $lama = $this->normalkan($jalurLama);
        $baru = $this->normalkan($jalurBaru);
        if ($lama === $baru) {
            throw ValidationException::withMessages(['jalur_baru' => 'Jalur tujuan tidak boleh sama dengan jalur lama.']);
        }
        if (str_starts_with($lama, '/admin') || str_starts_with($baru, '/admin')) {
            throw ValidationException::withMessages(['jalur_lama' => 'Jalur admin tidak dapat dialihkan.']);
        }
        $langkah = 0;
        $kini = $baru;
        while ($langkah < 5) {
            $berikut = PengalihanUrl::query()->where('jalur_lama', $kini)->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))->value('jalur_baru');
            if (! $berikut) {
                return;
            }
            if ($berikut === $lama) {
                throw ValidationException::withMessages(['jalur_baru' => 'Pengalihan ini akan membentuk loop.']);
            }
            $kini = $berikut;
            $langkah++;
        }
        throw ValidationException::withMessages(['jalur_baru' => 'Rantai pengalihan terlalu panjang. Arahkan langsung ke URL akhir.']);
    }

    public function normalkan(string $jalur): string
    {
        $jalur = trim($jalur);
        if (preg_match('#^https?://#i', $jalur)) {
            $host = parse_url($jalur, PHP_URL_HOST);
            $hostApp = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($host !== $hostApp) {
                throw ValidationException::withMessages(['jalur_baru' => 'Hanya jalur internal yang dapat digunakan.']);
            }
            $jalur = (string) parse_url($jalur, PHP_URL_PATH);
        }
        if (! str_starts_with($jalur, '/') || str_starts_with($jalur, '//') || preg_match('/[\s<>"\'\\\\?#]/', $jalur)) {
            throw ValidationException::withMessages(['jalur_baru' => 'Jalur harus diawali "/" dan tidak memuat spasi, query, atau karakter khusus.']);
        }

        return Str::limit('/'.trim($jalur, '/'), 190, '') ?: '/';
    }
}
