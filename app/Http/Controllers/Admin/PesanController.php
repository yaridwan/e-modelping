<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPesan;
use App\Http\Requests\Admin\AksiMassalPesanRequest;
use App\Http\Requests\Admin\PerbaruiPesanRequest;
use App\Http\Requests\Admin\StatusPesanRequest;
use App\Layanan\LayananPengaturan;
use App\Models\LogAktivitas;
use App\Models\PesanKontak;
use App\Support\EksporCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pesan masuk: tidak tampil publik, tanpa balasan otomatis.
 * Aksi: lihat, ubah status (baru/dibaca/ditindaklanjuti/arsip), catatan internal,
 * aksi massal, hapus permanen, dan ekspor CSV. Isi pesan tidak pernah ditulis ke log aktivitas.
 */
class PesanController extends AdminController
{
    /** Filter khusus: tanpa status = kotak masuk (semua kecuali arsip). */
    private const SEMUA = 'semua';

    public function index(Request $request): Response
    {
        $p = $this->parameterDaftar($request, ['dibuat_pada', 'nama_pengirim', 'status', 'subjek'], 'dibuat_pada', 'desc');
        $status = $this->statusFilter($request);
        $demo = $request->boolean('demo');

        return Inertia::render('admin/pesan/index', [
            'pesan' => $this->query($p['q'], $status, $demo)->orderBy($p['urut'], $p['arah'])->orderByDesc('id')->paginate($p['per'])->withQueryString()
                ->through(fn (PesanKontak $m) => [
                    'id' => $m->id,
                    'nama' => $m->nama_pengirim,
                    'surel' => $m->surel_pengirim,
                    'institusi' => $m->institusi,
                    'subjek' => $m->subjek,
                    'cuplikan' => Str::limit((string) preg_replace('/\s+/u', ' ', $m->isi_pesan), 120),
                    'status' => $m->status->value,
                    'catatan' => filled($m->catatan_admin),
                    'demo' => $m->data_contoh,
                    'dibuat_pada' => $m->dibuat_pada?->toIso8601String(),
                ]),
            'filter' => $p + ['status' => $status, 'demo' => $demo],
            'jumlah' => collect(StatusPesan::cases())->mapWithKeys(fn ($s) => [$s->value => PesanKontak::query()->where('data_contoh', $demo)->where('status', $s)->count()])->all(),
            'ada_demo' => PesanKontak::query()->where('data_contoh', true)->exists(),
        ]);
    }

    public function show(PesanKontak $pesan): Response
    {
        if ($pesan->status === StatusPesan::Baru) {
            $pesan->forceFill(['status' => StatusPesan::Dibaca, 'dibaca_pada' => now()])->save();
            $this->audit->catat('baca_pesan', 'pesan_kontak', $pesan->id, 'Pesan ditandai dibaca');
        }

        $dasar = fn () => PesanKontak::query()->where('data_contoh', $pesan->data_contoh);
        $waktu = $pesan->dibuat_pada;

        // Urutan kotak masuk: terbaru di atas. "Lebih baru" = sebelumnya, "lebih lama" = berikutnya.
        $lebihBaru = $dasar()->where(fn ($w) => $w->where('dibuat_pada', '>', $waktu)->orWhere(fn ($x) => $x->where('dibuat_pada', $waktu)->where('id', '>', $pesan->id)))
            ->orderBy('dibuat_pada')->orderBy('id')->value('id');
        $lebihLama = $dasar()->where(fn ($w) => $w->where('dibuat_pada', '<', $waktu)->orWhere(fn ($x) => $x->where('dibuat_pada', $waktu)->where('id', '<', $pesan->id)))
            ->orderByDesc('dibuat_pada')->orderByDesc('id')->value('id');

        $lain = $dasar()->where('surel_pengirim', $pesan->surel_pengirim)->whereKeyNot($pesan->id);

        return Inertia::render('admin/pesan/show', [
            'pesan' => [
                ...$pesan->only(['id', 'nama_pengirim', 'surel_pengirim', 'institusi', 'subjek', 'isi_pesan', 'catatan_admin', 'persetujuan_privasi', 'data_contoh']),
                'status' => $pesan->status->value,
                'dibuat_pada' => $pesan->dibuat_pada?->toIso8601String(),
                'dibaca_pada' => $pesan->dibaca_pada?->toIso8601String(),
                'ditindaklanjuti_pada' => $pesan->ditindaklanjuti_pada?->toIso8601String(),
            ],
            'navigasi' => ['sebelumnya' => $lebihBaru, 'berikutnya' => $lebihLama],
            'pesan_lain' => [
                'total' => (clone $lain)->count(),
                'data' => $lain->latest('dibuat_pada')->limit(5)->get(['id', 'subjek', 'status', 'dibuat_pada'])
                    ->map(fn (PesanKontak $m) => ['id' => $m->id, 'subjek' => $m->subjek, 'status' => $m->status->value, 'dibuat_pada' => $m->dibuat_pada?->toIso8601String()])->all(),
            ],
            'riwayat' => LogAktivitas::query()->with('pengguna:id,nama_lengkap')
                ->where('jenis_entitas', 'pesan_kontak')->where('entitas_id', $pesan->id)
                ->latest('dibuat_pada')->latest('id')->limit(15)->get()
                ->map(fn (LogAktivitas $l) => ['id' => $l->id, 'ringkasan' => $l->ringkasan, 'oleh' => $l->pengguna?->nama_lengkap, 'pada' => $l->dibuat_pada?->toIso8601String()])->all(),
        ]);
    }

    /** Simpan status + catatan internal dari formulir tindak lanjut. */
    public function update(PerbaruiPesanRequest $request, PesanKontak $pesan): RedirectResponse
    {
        $data = $request->validated();
        $status = StatusPesan::from($data['status']);
        $this->terapkanStatus($pesan, $status);
        $pesan->catatan_admin = $data['catatan_admin'] ?? null;
        $pesan->save();
        $this->audit->catat('perbarui_pesan', 'pesan_kontak', $pesan->id, 'Status pesan: '.$status->label(), ['status' => $status->value, 'catatan_admin' => $data['catatan_admin'] ?? null]);

        return $this->sukses('Pesan diperbarui.');
    }

    /** Aksi cepat: ubah status saja (catatan internal tidak tersentuh). */
    public function status(StatusPesanRequest $request, PesanKontak $pesan): RedirectResponse
    {
        $status = StatusPesan::from($request->validated('status'));
        $this->terapkanStatus($pesan, $status);
        $pesan->save();
        $this->audit->catat('status_pesan', 'pesan_kontak', $pesan->id, 'Status pesan: '.$status->label(), ['status' => $status->value]);

        $pesanSukses = match ($status) {
            StatusPesan::Baru => 'Pesan ditandai belum dibaca.',
            StatusPesan::Dibaca => 'Pesan ditandai dibaca.',
            StatusPesan::Ditindaklanjuti => 'Pesan ditandai sudah ditindaklanjuti.',
            StatusPesan::Arsip => 'Pesan diarsipkan.',
        };

        // Membuka detail otomatis menandai "dibaca"; karena itu "belum dibaca" kembali ke daftar.
        if ($status === StatusPesan::Baru && $this->dariDetail($pesan)) {
            return $this->sukses($pesanSukses, route('admin.pesan.index'));
        }

        return $this->sukses($pesanSukses);
    }

    public function massal(AksiMassalPesanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $id = array_map('intval', $data['id']);
        $aksi = $data['aksi'];

        $jumlah = DB::transaction(function () use ($id, $aksi) {
            $daftar = PesanKontak::query()->whereKey($id)->lockForUpdate()->get();
            foreach ($daftar as $m) {
                if ($aksi === 'hapus') {
                    $m->delete();

                    continue;
                }
                $this->terapkanStatus($m, StatusPesan::from($aksi));
                $m->save();
            }

            return $daftar->count();
        });

        $label = $aksi === 'hapus' ? 'dihapus permanen' : 'ditandai '.mb_strtolower(StatusPesan::from($aksi)->label());
        $this->audit->catat('pesan_massal', 'pesan_kontak', null, "{$jumlah} pesan {$label}", ['aksi' => $aksi, 'id' => $id]);

        return $jumlah
            ? $this->sukses("{$jumlah} pesan {$label}.")
            : back()->with('galat', 'Pesan yang dipilih tidak ditemukan.');
    }

    public function destroy(PesanKontak $pesan): RedirectResponse
    {
        $id = $pesan->id;
        $dariDetail = $this->dariDetail($pesan);
        $pesan->delete();
        $this->audit->catat('hapus_pesan', 'pesan_kontak', $id, "Pesan #{$id} dihapus permanen");

        return $this->sukses('Pesan dihapus permanen.', $dariDetail ? route('admin.pesan.index') : null);
    }

    public function ekspor(Request $request, LayananPengaturan $pengaturan): StreamedResponse
    {
        $status = $this->statusFilter($request);
        $demo = $request->boolean('demo');
        $tz = $pengaturan->zonaWaktu();
        $this->audit->catat('ekspor', 'pesan_kontak', null, 'Ekspor CSV pesan masuk', ['status' => $status, 'demo' => $demo]);

        $baris = (function () use ($status, $demo, $tz, $request) {
            foreach ($this->query((string) $request->query('q', ''), $status, $demo)->orderByDesc('dibuat_pada')->cursor() as $m) {
                yield [$m->id, $m->dibuat_pada?->timezone($tz)->format('Y-m-d H:i'), $m->nama_pengirim, $m->surel_pengirim, $m->institusi, $m->subjek, $m->isi_pesan, $m->status->label(), $m->catatan_admin, $m->data_contoh ? 'Demo' : 'Nyata'];
            }
        })();

        return EksporCsv::unduh('pesan-masuk-'.now()->timezone($tz)->format('Ymd-His').'.csv', ['ID', 'Diterima', 'Nama', 'Surel', 'Institusi', 'Subjek', 'Isi pesan', 'Status', 'Catatan admin', 'Jenis data'], $baris);
    }

    /** Status tujuan beserta stempel waktunya (belum disimpan). */
    private function terapkanStatus(PesanKontak $pesan, StatusPesan $status): void
    {
        $pesan->status = $status;
        if ($status === StatusPesan::Baru) {
            $pesan->dibaca_pada = null;

            return;
        }
        $pesan->dibaca_pada ??= now();
        if ($status === StatusPesan::Ditindaklanjuti) {
            $pesan->ditindaklanjuti_pada ??= now();
        }
    }

    private function dariDetail(PesanKontak $pesan): bool
    {
        $sebelumnya = (string) parse_url(url()->previous(), PHP_URL_PATH);

        return rtrim($sebelumnya, '/') === '/admin/pesan/'.$pesan->id;
    }

    /** null = kotak masuk (tanpa arsip), 'semua' = termasuk arsip, selain itu status tertentu. */
    private function statusFilter(Request $request): ?string
    {
        $status = $request->query('status');

        return in_array($status, [...StatusPesan::nilai(), self::SEMUA], true) ? $status : null;
    }

    private function query(string $q, ?string $status, bool $demo): Builder
    {
        $q = mb_substr(trim($q), 0, 100);

        return PesanKontak::query()->where('data_contoh', $demo)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nama_pengirim', 'like', self::pola($q))->orWhere('subjek', 'like', self::pola($q))->orWhere('surel_pengirim', 'like', self::pola($q))->orWhere('institusi', 'like', self::pola($q))))
            ->when($status === null, fn ($w) => $w->where('status', '!=', StatusPesan::Arsip->value))
            ->when($status !== null && $status !== self::SEMUA, fn ($w) => $w->where('status', $status));
    }
}
