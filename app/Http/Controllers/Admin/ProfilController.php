<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SimpanProfilRequest;
use App\Layanan\LayananMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfilController extends AdminController
{
    public function edit(Request $request): Response
    {
        $u = $request->user()->load('fotoProfil');

        return Inertia::render('admin/profil/edit', [
            'profil' => [
                ...$u->only(['nama_lengkap', 'nama_panggilan', 'surel', 'nomor_telepon', 'jabatan', 'institusi', 'bidang_keahlian', 'biografi', 'situs_pribadi', 'surel_publik', 'nomor_kontak_publik', 'tampilkan_profil_publik', 'tampilkan_foto_publik', 'tampilkan_kontak_publik', 'tema_pilihan']),
                'inisial' => $u->inisial(),
                'foto' => $u->fotoProfil?->urlAdmin(),
                'terakhir_masuk_pada' => $u->terakhir_masuk_pada?->toIso8601String(),
            ],
        ]);
    }

    public function update(SimpanProfilRequest $request): RedirectResponse
    {
        $u = $request->user();
        $u->fill($request->validated())->save();
        $this->audit->catatModel('perbarui_profil', $u, 'Perbarui profil admin');

        return $this->sukses('Profil disimpan.');
    }

    public function unggahFoto(Request $request, LayananMedia $media): RedirectResponse
    {
        $request->validate(['foto' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp']], [], ['foto' => 'foto profil']);
        $u = $request->user();
        $m = $media->unggahGambar($request->file('foto'), ['judul' => 'Foto profil '.$u->nama_lengkap, 'teks_alternatif' => 'Foto '.$u->nama_lengkap], 'foto');
        $u->forceFill(['foto_profil_id' => $m->id])->save();
        $this->audit->catat('ganti_foto', 'pengguna', $u->id, 'Ganti foto profil');

        return $this->sukses('Foto profil diperbarui.');
    }

    public function hapusFoto(Request $request): RedirectResponse
    {
        $u = $request->user();
        $u->forceFill(['foto_profil_id' => null])->save();
        $this->audit->catat('hapus_foto', 'pengguna', $u->id, 'Hapus foto profil (avatar inisial dipakai)');

        return $this->sukses('Foto profil dihapus. Avatar inisial digunakan.');
    }

    public function tema(Request $request): RedirectResponse
    {
        $data = $request->validate(['tema_pilihan' => ['required', Rule::in(['terang', 'gelap', 'sistem'])]]);
        $request->user()->forceFill($data)->save();

        return back();
    }
}
