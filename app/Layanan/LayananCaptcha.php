<?php

namespace App\Layanan;

use Illuminate\Contracts\Session\Session;

/**
 * Captcha aritmetika sederhana untuk login admin.
 *
 * - Soal dibuat dengan random_int (CSPRNG) di server.
 * - Jawaban TIDAK pernah dikirim ke klien; sesi hanya menyimpan HMAC jawaban, ID tantangan,
 *   dan waktu kedaluwarsa.
 * - Sekali pakai: setiap verifikasi (berhasil atau gagal) menghapus tantangan.
 * - Melengkapi, bukan menggantikan, pembatas laju percobaan login.
 */
class LayananCaptcha
{
    private const KUNCI_SESI = 'captcha_admin';

    public function __construct(private readonly Session $sesi) {}

    /** @return array{id: string, soal: string, kedaluwarsa_detik: int} */
    public function buat(): array
    {
        if (random_int(0, 1) === 1) {
            $a = random_int(2, 12);
            $b = random_int(1, 9);
            $soal = "{$a} + {$b}";
            $jawaban = $a + $b;
        } else {
            $a = random_int(6, 18);
            $b = random_int(1, min(9, $a));
            $soal = "{$a} − {$b}";
            $jawaban = $a - $b;
        }

        $id = bin2hex(random_bytes(16));
        $masa = (int) config('ping.captcha.masa_berlaku_detik', 300);

        $this->sesi->put(self::KUNCI_SESI, [
            'id' => $id,
            'hash' => $this->hash($id, (string) $jawaban),
            'kedaluwarsa' => now()->getTimestamp() + $masa,
        ]);

        return ['id' => $id, 'soal' => $soal, 'kedaluwarsa_detik' => $masa];
    }

    /** Soal yang sedang aktif untuk ditampilkan ulang (tanpa jawaban); buat baru bila tidak ada. */
    public function soalAktifAtauBaru(): array
    {
        return $this->buat();
    }

    /**
     * @return 'valid'|'salah'|'kedaluwarsa'|'tidak_ada'
     */
    public function verifikasi(?string $id, ?string $jawaban): string
    {
        $data = $this->sesi->pull(self::KUNCI_SESI);

        if (! is_array($data) || ! $id || ! hash_equals((string) $data['id'], $id)) {
            return 'tidak_ada';
        }

        if (now()->getTimestamp() > (int) $data['kedaluwarsa']) {
            return 'kedaluwarsa';
        }

        $jawaban = trim((string) $jawaban);
        if ($jawaban === '' || ! preg_match('/^\d{1,3}$/', $jawaban)) {
            return 'salah';
        }

        return hash_equals((string) $data['hash'], $this->hash($id, (string) (int) $jawaban)) ? 'valid' : 'salah';
    }

    public static function pesan(string $hasil): string
    {
        return match ($hasil) {
            'kedaluwarsa' => 'Soal keamanan sudah kedaluwarsa. Silakan jawab soal yang baru.',
            'tidak_ada' => 'Soal keamanan tidak ditemukan atau sudah dipakai. Silakan jawab soal yang baru.',
            default => 'Jawaban soal keamanan belum tepat. Silakan coba soal yang baru.',
        };
    }

    private function hash(string $id, string $jawaban): string
    {
        return hash_hmac('sha256', $id.'|'.$jawaban, (string) config('app.key'));
    }
}
