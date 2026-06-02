<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::create([
            'name' => 'z8venta',
            'alias' => 'z8venta',
            'currency' => 'BOB',
            'print_format' => '58mm',
            'admin_hour_start' => '00:00:00',
            'admin_hour_end' => '23:59:59',
            'encargado_hour_start' => '08:00:00',
            'encargado_hour_end' => '20:00:00',
            'activity' => 'Ventas e Inventario'
        ]);
    }
}