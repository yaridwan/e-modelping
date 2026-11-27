<?php

namespace App\Layanan;

use App\Enums\StatusKonten;
use App\Models\MenuNavigasi;
use App\Models\TautanSosial;

/** Menu navigasi publik dari database; tautan ke konten draf/arsip otomatis disembunyikan. */
class LayananNavigasi
{
    public function __construct(private readonly LayananPengaturan $pengaturan) {}

    /** @return array{utama: list<array>, footer: list<array>, sosial: list<array>} */
    public function publik(): array
    {
        return $this->pengaturan->ingat('navigasi', function () {
            $menu = MenuNavigasi::query()
                ->with(['halaman:id,slug,status', 'bab:id,slug,status'])
                ->where('aktif', true)
                ->orderBy('urutan')
                ->get();

            $susun = function ($lokasi, $indukId = null) use (&$susun, $menu) {
                return $menu->where('lokasi', $lokasi)->where('induk_id', $indukId)->values()
                    ->map(function (MenuNavigasi $m) use ($susun, $lokasi) {
                        $href = $this->href($m);
                        if ($href === null) {
                            return null;
                        }

                        return [
                            'id' => $m->id,
                            'label' => $m->label,
                            'href' => $href,
                            'eksternal' => $m->jenis_tautan === 'eksternal',
                            'tab_baru' => $m->buka_tab_baru,
                            'ikon' => $m->ikon,
                            'anak' => $susun($lokasi, $m->id),
                        ];
                    })->filter()->values()->all();
            };

            return [
                'utama' => $susun('utama'),
                'footer' => $susun('footer'),
                'sosial' => TautanSosial::query()->where('aktif', true)->whereNotNull('url')->orderBy('urutan')
                    ->get(['nama_platform', 'label', 'url', 'ikon'])->toArray(),
            ];
        });
    }

    public function href(MenuNavigasi $m): ?string
    {
        return match ($m->jenis_tautan) {
            'halaman' => $m->halaman && $m->halaman->status === StatusKonten::Terbit ? '/'.$m->halaman->slug : null,
            'bab' => $m->bab && $m->bab->status === StatusKonten::Terbit ? '/modul/'.$m->bab->slug : null,
            default => LayananSanitasiHtml::urlAman($m->url) && $m->url ? $m->url : null,
        };
    }
}
