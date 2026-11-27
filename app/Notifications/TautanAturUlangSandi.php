<?php

namespace App\Notifications;

use App\Layanan\LayananPengaturan;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TautanAturUlangSandi extends Notification
{
    public function __construct(private readonly string $url, private readonly int $menit) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nama = app(LayananPengaturan::class)->ambil()->nama_aplikasi ?: config('app.name');
        // Templat surel bawaan memakai app.name pada kepala/kaki surel: selaraskan dengan identitas.
        config(['app.name' => $nama]);

        return (new MailMessage)
            ->subject("[{$nama}] Atur ulang kata sandi")
            ->greeting('Halo,')
            ->line("Kami menerima permintaan untuk mengatur ulang kata sandi akun admin {$nama}.")
            ->action('Atur ulang kata sandi', $this->url)
            ->line("Tautan ini berlaku {$this->menit} menit dan hanya dapat dipakai satu kali.")
            ->line('Jika Anda tidak meminta pengaturan ulang, abaikan surel ini; sandi Anda tidak berubah.')
            ->salutation('Salam, '.$nama);
    }
}
