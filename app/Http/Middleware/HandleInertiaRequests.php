<?php

namespace App\Http\Middleware;

use App\Enums\StatusPesan;
use App\Layanan\LayananNavigasi;
use App\Layanan\LayananPengaturan;
use App\Models\PesanKontak;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props bersama. Halaman publik tidak pernah menerima surel akun, nomor pribadi,
     * metadata berkas privat, maupun token. Data akun hanya dibagikan pada rute admin.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $pengaturan = app(LayananPengaturan::class);
        $p = $pengaturan->ambil();
        $admin = $request->is('admin', 'admin/*');
        $pengguna = $request->user();

        return [
            ...parent::share($request),
            'situs' => [
                'nama_aplikasi' => $p->nama_aplikasi,
                'nama_singkat' => $p->nama_singkat,
                'tagline' => $p->tagline,
                'deskripsi_singkat' => $p->deskripsi_singkat,
                'logo_terang' => $p->logoTerang?->urlPublik(),
                'logo_gelap' => $p->logoGelap?->urlPublik(),
                // Rasio lebar:tinggi logo terang; > 1,4 dianggap logo persegi panjang bertulisan (nama tidak diulang sebagai teks).
                'logo_rasio' => $p->logoTerang?->lebar_piksel && $p->logoTerang->tinggi_piksel
                    ? round($p->logoTerang->lebar_piksel / $p->logoTerang->tinggi_piksel, 3)
                    : null,
                'favicon' => $p->favicon?->urlPublik(),
                'warna_utama' => $p->warna_utama,
                'warna_pendamping' => $p->warna_pendamping,
                'tema_bawaan' => $p->tema_bawaan,
                'teks_footer' => $p->teks_footer,
                'tahun_hak_cipta_awal' => $p->tahun_hak_cipta_awal,
                'tahun_sekarang' => (int) now()->timezone($pengaturan->zonaWaktu())->format('Y'),
                'nama_pemilik' => $p->nama_pemilik,
                'nama_institusi' => $p->nama_institusi,
                'kontak' => [
                    'surel' => $p->surel_kontak,
                    'whatsapp' => $p->nomor_whatsapp,
                    'alamat' => $p->alamat_publik,
                    'kota' => $p->kota_kabupaten,
                    'provinsi' => $p->provinsi,
                ],
            ],
            'navigasi' => fn () => $admin ? null : app(LayananNavigasi::class)->publik(),
            'auth' => [
                'masuk' => (bool) $pengguna,
                'pengguna' => $admin && $pengguna ? [
                    'id' => $pengguna->id,
                    'nama_lengkap' => $pengguna->nama_lengkap,
                    'nama_panggilan' => $pengguna->nama_panggilan,
                    'surel' => $pengguna->surel,
                    'inisial' => $pengguna->inisial(),
                    'foto' => $pengguna->fotoProfil?->urlAdmin(),
                    'tema_pilihan' => $pengguna->tema_pilihan,
                    'wajib_ganti_sandi' => $pengguna->wajib_ganti_sandi,
                ] : null,
            ],
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'galat' => fn () => $request->session()->get('galat'),
            ],
            // Lencana menu admin (hanya rute admin & sudah masuk): jumlah pesan nyata berstatus baru.
            'lencana_admin' => fn () => $admin && $pengguna
                ? ['pesan_baru' => PesanKontak::query()->where('data_contoh', false)->where('status', StatusPesan::Baru)->count()]
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
