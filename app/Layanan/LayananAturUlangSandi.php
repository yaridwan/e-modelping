<?php

namespace App\Layanan;

use App\Models\Pengguna;
use App\Models\TokenAturUlangSandi;
use App\Notifications\TautanAturUlangSandi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pemulihan sandi dengan tabel `token_atur_ulang_sandi` (kolom Indonesia), menggantikan
 * broker bawaan Laravel. Token 64 karakter acak; database hanya menyimpan HMAC-SHA256.
 * Token sekali pakai, berlaku 60 menit, dan token lama dicabut saat token baru dibuat.
 */
class LayananAturUlangSandi
{
    public const MASA_BERLAKU_MENIT = 60;

    public function __construct(private readonly LayananAudit $audit) {}

    /** Selalu mengembalikan void agar respons ke klien generik (tidak membocorkan keberadaan akun). */
    public function kirimTautan(string $surel): void
    {
        $pengguna = Pengguna::query()->where('surel', mb_strtolower(trim($surel)))->first();
        if (! $pengguna || ! $pengguna->aktif()) {
            return;
        }

        $token = Str::random(64);

        DB::transaction(function () use ($pengguna, $token) {
            TokenAturUlangSandi::query()->where('pengguna_id', $pengguna->id)->whereNull('digunakan_pada')->delete();
            TokenAturUlangSandi::query()->create([
                'pengguna_id' => $pengguna->id,
                'token_hash' => self::hash($token),
                'kedaluwarsa_pada' => now()->addMinutes(self::MASA_BERLAKU_MENIT),
                'dibuat_pada' => now(),
            ]);
        });

        // URL dibangun dari APP_URL tepercaya, bukan dari header Host permintaan.
        $url = rtrim((string) config('app.url'), '/').'/admin/atur-ulang-sandi/'.$token;
        $pengguna->notify(new TautanAturUlangSandi($url, self::MASA_BERLAKU_MENIT));
        $this->audit->catat('minta_reset_sandi', 'pengguna', $pengguna->id, 'Permintaan tautan atur ulang sandi dikirim', null, $pengguna->id);
    }

    public function tokenValid(string $token): ?TokenAturUlangSandi
    {
        if (strlen($token) !== 64) {
            return null;
        }

        return TokenAturUlangSandi::query()
            ->where('token_hash', self::hash($token))
            ->whereNull('digunakan_pada')
            ->where('kedaluwarsa_pada', '>', now())
            ->first();
    }

    /** @return bool true bila berhasil */
    public function aturUlang(string $token, string $surel, string $sandiBaru): bool
    {
        return DB::transaction(function () use ($token, $surel, $sandiBaru) {
            $catatan = TokenAturUlangSandi::query()
                ->where('token_hash', self::hash($token))
                ->lockForUpdate()
                ->first();

            if (! $catatan || $catatan->digunakan_pada || $catatan->kedaluwarsa_pada->isPast()) {
                return false;
            }

            $pengguna = $catatan->pengguna;
            if (! $pengguna || ! hash_equals(mb_strtolower($pengguna->surel), mb_strtolower(trim($surel))) || ! $pengguna->aktif()) {
                return false;
            }

            $pengguna->forceFill([
                'kata_sandi' => Hash::make($sandiBaru),
                'token_pengingat' => Str::random(60),
                'sandi_diubah_pada' => now(),
                'wajib_ganti_sandi' => false,
            ])->save();

            $catatan->forceFill(['digunakan_pada' => now()])->save();
            TokenAturUlangSandi::query()->where('pengguna_id', $pengguna->id)->whereNull('digunakan_pada')->delete();

            $this->audit->catat('reset_sandi', 'pengguna', $pengguna->id, 'Sandi diatur ulang melalui tautan pemulihan', null, $pengguna->id);

            return true;
        });
    }

    public static function hash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
