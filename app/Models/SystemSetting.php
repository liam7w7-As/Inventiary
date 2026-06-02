<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'alias', 'logo', 'activity', 'currency',
        'admin_hour_start', 'admin_hour_end',
        'encargado_hour_start', 'encargado_hour_end',
        'print_format'
    ];
}