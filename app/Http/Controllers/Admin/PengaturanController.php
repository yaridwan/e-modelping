<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanPengaturanRequest;
use App\Layanan\LayananMedia;
use App\Layanan\LayananPengaturan;
use App\Models\PengaturanWebsite;
use App\Models\VersiDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengaturan website per tab. Domain kanonis TIDAK dapat diubah di sini: berasal dari APP_URL.
 * Kredensial DB/SMTP/APP_KEY berada di environment dan tidak pernah ditampilkan.
 */
class PengaturanController extends AdminController
{
    private const MEDIA = ['logo_terang' => 'logo_terang_id', 'logo_gelap' => 'logo_gelap_id', 'favicon' => 'favicon_id', 'gambar_sampul' => 'gambar_sampul_id', 'gambar_og' => 'gambar_og_id'];

    public function index(LayananMedia $media): Response
    {
        $p = PengaturanWebsite::query()->with(['logoTerang', 'logoGelap', 'favicon', 'gambarSampul', 'gambarOg'])->where('kunci_tunggal', 'utama')->firstOrFail();
        $gambar = fn ($m) => $m ? ['id' => $m->id, 'url' => $m->urlAdmin(), 'judul' => $m->judul] : null;
        $modulAktif = VersiDokumen::query()->where('jenis_dokumen', 'modul_lengkap')->where('versi_aktif', true)->first();

        return Inertia::render('admin/pengaturan/index', [
            'pengaturan' => [
                ...collect($p->toArray())->except(['logo_terang', 'logo_gelap', 'favicon', 'gambar_sampul', 'gambar_og', 'kunci_tunggal'])->all(),
                'media' => [
                    'logo_terang' => $gambar($p->logoTerang), 'logo_gelap' => $gambar($p->logoGelap), 'favicon' => $gambar($p->favicon),
                    'gambar_sampul' => $gambar($p->gambarSampul), 'gambar_og' => $gambar($p->gambarOg),
                ],
            ],
            'info' => [
                'domain_kanonis' => config('app.url'),
                'batas_server_mb' => (int) config('ping.batas_unggah_maksimum_mb'),
                'batas_php' => ['upload_max_filesize' => ini_get('upload_max_filesize'), 'post_max_size' => ini_get('post_max_size')],
                'modul_lengkap_aktif' => $modulAktif ? ['id' => $modulAktif->id, 'judul' => $modulAktif->judul_dokumen, 'nomor_versi' => $modulAktif->nomor_versi] : null,
                'mailer' => (string) config('mail.default'),
            ],
            'tab' => request()->query('tab', 'identitas'),
        ]);
    }

    public function update(SimpanPengaturanRequest $request, string $tab, LayananMedia $media, LayananPengaturan $layanan): RedirectResponse
    {
        $p = PengaturanWebsite::query()->where('kunci_tunggal', 'utama')->firstOrFail();
        $data = collect($request->validated())->except(array_merge(array_keys(self::MEDIA), array_map(fn ($k) => 'hapus_'.$k, array_keys(self::MEDIA))))->all();
        $mediaBaru = [];

        DB::transaction(function () use ($request, $p, $data, $media, &$mediaBaru) {
            foreach (self::MEDIA as $kolomBerkas => $kolomId) {
                if ($request->hasFile($kolomBerkas)) {
                    $m = $media->unggahGambar($request->file($kolomBerkas), [
                        'judul' => ucfirst(str_replace('_', ' ', $kolomBerkas)).' situs',
                        'teks_alternatif' => 'Logo '.($data['nama_aplikasi'] ?? $p->nama_aplikasi),
                    ], $kolomBerkas);
                    $mediaBaru[] = $m;
                    $data[$kolomId] = $m->id;
                } elseif ($request->boolean('hapus_'.$kolomBerkas)) {
                    $data[$kolomId] = null;
                }
            }
            $p->fill($data)->forceFill(['diperbarui_oleh' => auth()->id()])->save();
            $this->audit->catatModel('perbarui_pengaturan', $p, 'Perbarui pengaturan website ('.request()->route('tab').')');
        });

        // Berkas lama tidak langsung dihapus; tetap di pustaka media untuk ditinjau admin.
        $layanan->bersihkan();

        return $this->sukses('Pengaturan '.$tab.' disimpan.', '/admin/pengaturan?tab='.$tab);
    }
}
