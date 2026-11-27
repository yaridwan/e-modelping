<?php

namespace App\Http\Controllers\Admin;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Log audit baca-saja: tidak ada rute ubah/hapus. */
class LogAktivitasController extends AdminController
{
    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['dibuat_pada', 'aksi', 'jenis_entitas'], 'dibuat_pada', 'desc');
        $aksi = (string) $request->query('aksi', '');
        $entitas = (string) $request->query('entitas', '');
        $demo = $request->boolean('demo');

        return Inertia::render('admin/log/index', [
            'log' => LogAktivitas::query()->with('pengguna:id,nama_lengkap')->where('data_contoh', $demo)
                ->when($p['q'] !== '', fn ($q) => $q->where('ringkasan', 'like', self::pola($p['q'])))
                ->when($aksi !== '', fn ($q) => $q->where('aksi', $aksi))
                ->when($entitas !== '', fn ($q) => $q->where('jenis_entitas', $entitas))
                ->orderBy($p['urut'], $p['arah'])->paginate($p['per'])->withQueryString()
                ->through(fn (LogAktivitas $l) => [
                    'id' => $l->id, 'aksi' => $l->aksi, 'jenis_entitas' => $l->jenis_entitas, 'entitas_id' => $l->entitas_id,
                    'ringkasan' => $l->ringkasan, 'perubahan' => $l->perubahan, 'pengguna' => $l->pengguna?->nama_lengkap,
                    'kode_permintaan' => $l->kode_permintaan, 'demo' => $l->data_contoh, 'dibuat_pada' => $l->dibuat_pada?->toIso8601String(),
                ]),
            'filter' => $p + ['aksi' => $aksi, 'entitas' => $entitas, 'demo' => $demo],
            'opsi_aksi' => LogAktivitas::query()->distinct()->orderBy('aksi')->pluck('aksi'),
            'opsi_entitas' => LogAktivitas::query()->distinct()->orderBy('jenis_entitas')->pluck('jenis_entitas'),
        ]);
    }
}
