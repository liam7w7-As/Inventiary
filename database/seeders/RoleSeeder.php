<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'admin', 'display_name' => 'Administrador']);
        Role::create(['name' => 'encargado', 'display_name' => 'Encargado de Sucursal']);
        Role::create(['name' => 'vendedor', 'display_name' => 'Vendedor']);
    }
}