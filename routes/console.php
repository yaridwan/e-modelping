<?php

use Illuminate\Support\Facades\Schedule;

// Jalankan `php artisan schedule:run` setiap menit melalui cron (lihat README bagian Deploy).
Schedule::command('ping:bersihkan-retensi')->dailyAt('02:30')->timezone('Asia/Jakarta');
