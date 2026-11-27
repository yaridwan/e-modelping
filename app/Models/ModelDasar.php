<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model dasar dengan nama kolom waktu berbahasa Indonesia.
 * Nilai waktu disimpan dalam UTC (config app.timezone) dan disajikan dalam zona waktu pengaturan.
 */
abstract class ModelDasar extends Model
{
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';
}
