<?php

namespace App\Layanan;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengurutan ulang beberapa baris secara atomik. Untuk menghindari bentrok indeks unik
 * sementara (mis. unique(bab_id, urutan)), nilai dipindah dulu ke rentang offset lalu ke nilai akhir.
 */
class LayananUrutan
{
    private const OFFSET = 30000;

    /**
     * @param  list<int>  $idBerurutan  ID dalam urutan baru (lengkap untuk lingkup tersebut)
     * @param  array<string, mixed>  $lingkup  kondisi pembatas, mis. ['bab_id' => 4]
     */
    public function terapkan(string $tabel, array $idBerurutan, array $lingkup = [], string $kolom = 'urutan'): void
    {
        $idBerurutan = array_values(array_map('intval', $idBerurutan));

        DB::transaction(function () use ($tabel, $idBerurutan, $lingkup, $kolom) {
            $query = DB::table($tabel)->where($lingkup)->lockForUpdate();
            $idAda = $query->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $idBaru = collect($idBerurutan)->sort()->values()->all();

            if ($idAda !== $idBaru || count(array_unique($idBerurutan)) !== count($idBerurutan)) {
                throw ValidationException::withMessages(['urutan' => 'Daftar urutan tidak lengkap atau tidak sesuai dengan data saat ini. Muat ulang halaman lalu coba lagi.']);
            }

            DB::table($tabel)->where($lingkup)->update([$kolom => DB::raw("{$kolom} + ".self::OFFSET)]);
            foreach ($idBerurutan as $posisi => $id) {
                DB::table($tabel)->where('id', $id)->update([$kolom => $posisi + 1]);
            }
        });
    }
}
