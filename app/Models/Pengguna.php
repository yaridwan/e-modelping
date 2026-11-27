<?php

namespace App\Models;

use App\Layanan\LayananPengaturan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun pemilik/admin. Kontrak autentikasi Laravel dipetakan ke kolom Indonesia:
 * surel (identitas), kata_sandi (hash), token_pengingat (remember token).
 */
class Pengguna extends Authenticatable
{
    use HasFactory, Notifiable;

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected $table = 'pengguna';

    protected $fillable = [
        'nama_lengkap', 'nama_panggilan', 'surel', 'nomor_telepon', 'jabatan', 'institusi',
        'bidang_keahlian', 'biografi', 'foto_profil_id', 'situs_pribadi', 'surel_publik',
        'nomor_kontak_publik', 'tampilkan_profil_publik', 'tampilkan_foto_publik',
        'tampilkan_kontak_publik', 'tema_pilihan',
    ];

    protected $hidden = ['kata_sandi', 'token_pengingat'];

    protected static function booted(): void
    {
        // Profil publik penyusun tampil di halaman publik: segarkan cache publik bila berubah.
        static::saved(fn () => app(LayananPengaturan::class)->naikkanVersiKonten());
    }

    protected function casts(): array
    {
        return [
            'kata_sandi' => 'hashed',
            'surel_diverifikasi_pada' => 'datetime',
            'terakhir_masuk_pada' => 'datetime',
            'sandi_diubah_pada' => 'datetime',
            'tampilkan_profil_publik' => 'boolean',
            'tampilkan_foto_publik' => 'boolean',
            'tampilkan_kontak_publik' => 'boolean',
            'wajib_ganti_sandi' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    public function getRememberTokenName(): string
    {
        return 'token_pengingat';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->surel;
    }

    public function routeNotificationForMail(): string
    {
        return $this->surel;
    }

    public function fotoProfil(): BelongsTo
    {
        return $this->belongsTo(BerkasMedia::class, 'foto_profil_id');
    }

    public function aktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function inisial(): string
    {
        $kata = preg_split('/\s+/', trim($this->nama_lengkap)) ?: [];

        return mb_strtoupper(collect($kata)->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->implode(''));
    }
}
