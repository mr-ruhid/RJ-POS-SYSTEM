<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Super Admin rolunu tapaq
        $adminRole = Role::where('slug', 'super_admin')->first();

        if ($adminRole) {
            User::create([
                'name' => 'Baş Admin',
                'email' => 'admin@rjpos.com',
                'password' => Hash::make('password'), // Parol: password
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]);

            $this->command->info('Admin istifadəçisi yaradıldı! Email: admin@rjpos.com, Parol: password');
        } else {
            $this->command->error('Super Admin rolu tapılmadı! Əvvəl RoleSeeder işlədin.');
        }
    }
}
