<?php

namespace App\Layanan;

use App\Models\LogAktivitas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Pencatat log audit. Perubahan disaring: kolom rahasia dibuang, teks panjang diringkas.
 */
class LayananAudit
{
    private const KOLOM_RAHASIA = [
        'kata_sandi', 'token_pengingat', 'token_hash', 'password', 'remember_token', 'sandi',
        'sandi_saat_ini', 'sandi_baru', 'isi_pesan', 'catatan_admin', 'checksum_sha256', 'jalur_berkas',
        'nama_simpan', 'captcha_jawaban', '_token',
    ];

    private const KOLOM_ABAIKAN = ['diperbarui_pada', 'dibuat_pada', 'lingkup_dokumen', 'kunci_versi_aktif'];

    public function __construct(private readonly LayananSidik $sidik) {}

    public function catat(string $aksi, string $jenisEntitas, ?int $entitasId, string $ringkasan, ?array $perubahan = null, ?int $penggunaId = null): LogAktivitas
    {
        $request = request();

        return LogAktivitas::query()->create([
            'pengguna_id' => $penggunaId ?? auth()->id(),
            'aksi' => Str::limit($aksi, 40, ''),
            'jenis_entitas' => Str::limit($jenisEntitas, 60, ''),
            'entitas_id' => $entitasId,
            'ringkasan' => Str::limit($ringkasan, 250),
            'perubahan' => $perubahan ? $this->saring($perubahan) : null,
            'sidik_pelaku' => $request ? $this->sidik->pengunjung($request) : null,
            'kode_permintaan' => $request?->attributes->get('kode_permintaan'),
            'data_contoh' => false,
            'dibuat_pada' => now(),
        ]);
    }

    /** Catat perubahan model setelah disimpan (memakai getChanges/getOriginal). */
    public function catatModel(string $aksi, Model $model, string $ringkasan): LogAktivitas
    {
        $perubahan = [];
        foreach ($model->getChanges() as $kolom => $baru) {
            if (in_array($kolom, self::KOLOM_ABAIKAN, true)) {
                continue;
            }
            $perubahan[$kolom] = ['sebelum' => $model->getRawOriginal($kolom), 'sesudah' => $baru];
        }

        return $this->catat($aksi, $model->getTable(), (int) $model->getKey(), $ringkasan, $perubahan ?: null);
    }

    public function saring(array $data): array
    {
        $hasil = [];
        foreach ($data as $kunci => $nilai) {
            if (in_array((string) $kunci, self::KOLOM_RAHASIA, true)) {
                $hasil[$kunci] = '[disembunyikan]';

                continue;
            }
            if (is_array($nilai)) {
                $hasil[$kunci] = Arr::isAssoc($nilai) || array_key_exists('sebelum', $nilai) ? $this->saring($nilai) : array_slice($nilai, 0, 20);

                continue;
            }
            if (is_string($nilai) && mb_strlen($nilai) > 200) {
                $nilai = mb_substr(LayananSanitasiHtml::teksPolos($nilai), 0, 160).'… ('.mb_strlen($nilai).' karakter)';
            }
            $hasil[$kunci] = $nilai;
        }

        return $hasil;
    }
}
