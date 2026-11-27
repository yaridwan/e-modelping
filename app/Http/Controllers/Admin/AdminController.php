<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Layanan\LayananAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class AdminController extends Controller
{
    public function __construct(protected readonly LayananAudit $audit) {}

    protected function sukses(string $pesan, ?string $ke = null): RedirectResponse
    {
        return ($ke ? redirect($ke) : back())->with('sukses', $pesan);
    }

    /**
     * Parameter daftar standar: pencarian, filter, urutan kolom yang diizinkan, ukuran halaman.
     *
     * @param  list<string>  $kolomUrut
     * @return array{q: string, urut: string, arah: string, per: int}
     */
    protected function parameterDaftar(Request $request, array $kolomUrut, string $urutBawaan, string $arahBawaan = 'asc'): array
    {
        $urut = (string) $request->query('urut', $urutBawaan);
        $arah = strtolower((string) $request->query('arah', $arahBawaan)) === 'desc' ? 'desc' : 'asc';
        $per = (int) $request->query('per', 15);

        return [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
            'urut' => in_array($urut, $kolomUrut, true) ? $urut : $urutBawaan,
            'arah' => $arah,
            'per' => in_array($per, [10, 15, 25, 50], true) ? $per : 15,
        ];
    }

    protected static function pola(string $q): string
    {
        return '%'.addcslashes($q, '%_\\').'%';
    }
}
