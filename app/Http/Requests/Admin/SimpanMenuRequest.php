<?php

namespace App\Http\Requests\Admin;

use App\Models\MenuNavigasi;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpanMenuRequest extends PermintaanAdmin
{
    public const IKON = ['house', 'book-open', 'info', 'images', 'circle-help', 'mail', 'shield', 'file-text', 'layers', 'sparkles', 'search', 'external-link'];

    public function rules(): array
    {
        return [
            'lokasi' => ['required', Rule::in(['utama', 'footer'])],
            'label' => ['required', 'string', 'max:80'],
            'jenis_tautan' => ['required', Rule::in(['halaman', 'bab', 'internal', 'eksternal'])],
            'halaman_id' => ['nullable', 'required_if:jenis_tautan,halaman', 'integer', Rule::exists('halaman_informasi', 'id')],
            'bab_id' => ['nullable', 'required_if:jenis_tautan,bab', 'integer', Rule::exists('bab_modul', 'id')],
            'url' => ['nullable', 'required_if:jenis_tautan,internal', 'required_if:jenis_tautan,eksternal', 'string', 'max:255'],
            'induk_id' => ['nullable', 'integer', Rule::exists('menu_navigasi', 'id')],
            'ikon' => ['nullable', Rule::in(self::IKON)],
            'buka_tab_baru' => ['boolean'],
            'aktif' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $jenis = $this->input('jenis_tautan');
            $url = (string) $this->input('url');
            if ($jenis === 'internal' && ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//') || preg_match('/[\s<>"\']/', $url))) {
                $v->errors()->add('url', 'Tautan internal harus berupa jalur yang diawali "/".');
            }
            if ($jenis === 'eksternal' && ! preg_match('#^https?://#i', $url)) {
                $v->errors()->add('url', 'Tautan eksternal harus diawali http:// atau https://.');
            } elseif ($jenis === 'eksternal' && ! filter_var($url, FILTER_VALIDATE_URL)) {
                $v->errors()->add('url', 'URL eksternal tidak valid.');
            }
            // Cegah siklus induk dan batasi kedalaman satu tingkat.
            $menu = $this->route('menu');
            $induk = $this->input('induk_id') ? MenuNavigasi::query()->find($this->input('induk_id')) : null;
            if ($induk) {
                if ($menu && ($induk->id === $menu->id || $induk->induk_id === $menu->id)) {
                    $v->errors()->add('induk_id', 'Menu tidak dapat menjadi induk dirinya sendiri (siklus).');
                }
                if ($induk->induk_id) {
                    $v->errors()->add('induk_id', 'Submenu hanya mendukung satu tingkat.');
                }
                if ($induk->lokasi !== $this->input('lokasi')) {
                    $v->errors()->add('induk_id', 'Induk harus berada pada lokasi menu yang sama.');
                }
                if ($menu && $menu->anak()->exists()) {
                    $v->errors()->add('induk_id', 'Menu yang memiliki submenu tidak dapat dijadikan submenu.');
                }
            }
        }];
    }

    /** Hanya target sesuai jenis yang disimpan; target lain dikosongkan (tepat satu target). */
    public function dataTersaring(): array
    {
        $d = $this->validated();
        $d['halaman_id'] = $d['jenis_tautan'] === 'halaman' ? $d['halaman_id'] : null;
        $d['bab_id'] = $d['jenis_tautan'] === 'bab' ? $d['bab_id'] : null;
        $d['url'] = in_array($d['jenis_tautan'], ['internal', 'eksternal'], true) ? $d['url'] : null;

        return $d;
    }
}
