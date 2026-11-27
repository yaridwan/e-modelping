<?php

namespace App\Http\Requests\Admin;

use App\Layanan\LayananSanitasiHtml;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

abstract class PermintaanAdmin extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('kelola-website');
    }

    /** Aturan URL aman (http/https/mailto atau jalur relatif internal). */
    protected function urlAman(bool $relatif = true): Closure
    {
        return function (string $atribut, mixed $nilai, Closure $gagal) use ($relatif) {
            if ($nilai !== null && $nilai !== '' && ! LayananSanitasiHtml::urlAman((string) $nilai, $relatif)) {
                $gagal('Tautan tidak valid. Gunakan URL http/https, mailto, atau jalur internal yang diawali "/".');
            }
        };
    }

    /** Daftar string (tujuan, poin penting, dsb.) dari textarea/array. */
    protected function aturanDaftar(int $maksItem = 20, int $maksPanjang = 300): array
    {
        return ['nullable', 'array', 'max:'.$maksItem];
    }

    protected function prepareForValidation(): void
    {
        $bersih = [];
        foreach ($this->all() as $kunci => $nilai) {
            if (is_string($nilai)) {
                $bersih[$kunci] = trim($nilai) === '' ? null : trim($nilai);
            }
        }
        $this->merge($bersih);
    }
}
