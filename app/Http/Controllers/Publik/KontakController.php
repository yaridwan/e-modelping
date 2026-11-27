<?php

namespace App\Http\Controllers\Publik;

use App\Enums\StatusPesan;
use App\Http\Requests\Publik\KirimPesanRequest;
use App\Layanan\LayananSeo;
use App\Layanan\LayananSidik;
use App\Models\HalamanInformasi;
use App\Models\PesanKontak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KontakController extends PublikController
{
    public function create(Request $request): Response
    {
        $halaman = HalamanInformasi::query()->terbit()->where('kode_halaman', 'kontak')->first();
        $this->tandai($request, 'kontak', null, $halaman?->id);

        return Inertia::render('publik/kontak', [
            'pengantar' => $halaman ? ['judul' => $halaman->judul, 'ringkasan' => $halaman->ringkasan, 'isi' => $halaman->isi] : null,
            'terkirim' => (bool) $request->session()->get('pesan_terkirim'),
            'seo' => $this->seo->bangun([
                'judul' => $halaman?->judul ?: 'Kontak',
                'deskripsi' => $halaman?->ringkasan ?: 'Hubungi penyusun E-Model PING melalui formulir pesan.',
                'jalur' => '/kontak',
                'jsonLd' => [LayananSeo::jsonLdBreadcrumb([['nama' => 'Beranda', 'url' => '/'], ['nama' => 'Kontak', 'url' => '/kontak']])],
            ]),
        ]);
    }

    public function store(KirimPesanRequest $request, LayananSidik $sidik): RedirectResponse
    {
        $data = $request->validated();
        $pesan = new PesanKontak;
        $pesan->forceFill([
            'nama_pengirim' => strip_tags($data['nama_pengirim']),
            'surel_pengirim' => mb_strtolower($data['surel_pengirim']),
            'institusi' => isset($data['institusi']) ? strip_tags($data['institusi']) : null,
            'subjek' => strip_tags($data['subjek']),
            'isi_pesan' => strip_tags($data['isi_pesan']),
            'persetujuan_privasi' => true,
            'status' => StatusPesan::Baru,
            'sidik_pengirim' => $sidik->pengunjung($request),
            'data_contoh' => false,
        ])->save();

        // Konfirmasi tanpa menampilkan ulang isi pesan.
        return redirect('/kontak')->with('pesan_terkirim', true)->with('sukses', 'Terima kasih, pesan Anda sudah kami terima.');
    }
}
