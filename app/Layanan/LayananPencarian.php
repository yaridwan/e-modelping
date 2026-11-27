<?php

namespace App\Layanan;

use App\Enums\StatusKonten;
use App\Models\BabModul;
use App\Models\FasePing;
use App\Models\HalamanInformasi;
use App\Models\SubbabModul;
use Illuminate\Support\Str;

/**
 * Pencarian materi HTML publik (judul, ringkasan, isi subbab terbit). Tidak mencari isi PDF.
 */
class LayananPencarian
{
    /** @return list<array{jenis: string, judul: string, konteks: string, cuplikan: string, url: string}> */
    public function cari(string $kata, int $batas = 30): array
    {
        $kata = trim(Str::limit($kata, 80, ''));
        if (mb_strlen($kata) < 2) {
            return [];
        }
        $pola = '%'.addcslashes($kata, '%_\\').'%';
        $terbit = StatusKonten::Terbit->value;
        $hasil = [];

        $bab = BabModul::query()->where('status', $terbit)
            ->where(fn ($q) => $q->where('judul', 'like', $pola)->orWhere('ringkasan', 'like', $pola)->orWhere('pengantar', 'like', $pola))
            ->orderBy('urutan')->limit($batas)->get(['id', 'nomor_romawi', 'judul', 'slug', 'ringkasan', 'pengantar']);
        foreach ($bab as $b) {
            $hasil[] = ['jenis' => 'Bab', 'judul' => "Bab {$b->nomor_romawi} · {$b->judul}", 'konteks' => 'Daftar bab', 'cuplikan' => $this->cuplikan($b->ringkasan.' '.$b->pengantar, $kata), 'url' => '/modul/'.$b->slug];
        }

        $subbab = SubbabModul::query()->with('bab:id,nomor_romawi,judul,slug')
            ->where('status', $terbit)
            ->whereHas('bab', fn ($q) => $q->where('status', $terbit))
            ->where(fn ($q) => $q->where('judul', 'like', $pola)->orWhere('ringkasan', 'like', $pola)->orWhere('isi', 'like', $pola)->orWhere('contoh_praktik', 'like', $pola))
            ->orderBy('bab_id')->orderBy('urutan')->limit($batas)->get(['id', 'bab_id', 'judul', 'slug', 'ringkasan', 'isi', 'contoh_praktik']);
        foreach ($subbab as $s) {
            $hasil[] = ['jenis' => 'Subbab', 'judul' => $s->judul, 'konteks' => "Bab {$s->bab->nomor_romawi} · {$s->bab->judul}", 'cuplikan' => $this->cuplikan($s->ringkasan.' '.$s->isi.' '.$s->contoh_praktik, $kata), 'url' => '/modul/'.$s->bab->slug.'#'.$s->slug];
        }

        $fase = FasePing::query()->where('status', $terbit)
            ->where(fn ($q) => $q->where('nama', 'like', $pola)->orWhere('judul_indonesia', 'like', $pola)->orWhere('deskripsi', 'like', $pola))
            ->limit(5)->get(['nama', 'slug', 'deskripsi']);
        foreach ($fase as $f) {
            $hasil[] = ['jenis' => 'Fase', 'judul' => $f->nama, 'konteks' => 'Fase PING', 'cuplikan' => $this->cuplikan($f->deskripsi, $kata), 'url' => '/fase/'.$f->slug];
        }

        $halaman = HalamanInformasi::query()->where('status', $terbit)
            ->where(fn ($q) => $q->where('judul', 'like', $pola)->orWhere('ringkasan', 'like', $pola)->orWhere('isi', 'like', $pola))
            ->limit(5)->get(['judul', 'slug', 'ringkasan', 'isi']);
        foreach ($halaman as $h) {
            $hasil[] = ['jenis' => 'Halaman', 'judul' => $h->judul, 'konteks' => 'Informasi', 'cuplikan' => $this->cuplikan($h->ringkasan.' '.$h->isi, $kata), 'url' => '/'.$h->slug];
        }

        return array_slice($hasil, 0, $batas);
    }

    private function cuplikan(?string $html, string $kata): string
    {
        $teks = LayananSanitasiHtml::teksPolos($html);
        $posisi = mb_stripos($teks, $kata);
        if ($posisi === false) {
            return Str::limit($teks, 180);
        }
        $mulai = max(0, $posisi - 70);

        return ($mulai > 0 ? '…' : '').trim(mb_substr($teks, $mulai, 200)).(mb_strlen($teks) > $mulai + 200 ? '…' : '');
    }
}
