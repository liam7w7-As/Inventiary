<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        
        User::create([
            'name' => 'Administrador',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
            'branch_id' => null,
            'bypass_schedule' => true,
            'can_view_profits' => true,
            'is_active' => true,
        ]);
    }
}