<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Xarici açar yoxlanışını müvəqqəti söndürürük (Xətasız silmək üçün)
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('roles')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Exception $e) {
            // Əgər SQLite istifadə edilirsə (Alternativ təmizləmə)
            DB::table('roles')->delete();
        }

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'permissions' => json_encode(['all' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mağaza Müdiri (Admin)',
                'slug' => 'admin',
                'permissions' => json_encode([
                    'products.create' => true,
                    'products.edit' => true,
                    'products.delete' => true,
                    'reports.view' => true,
                    'discounts.manage' => true,
                    'users.manage' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kassir',
                'slug' => 'kassa',
                'permissions' => json_encode([
                    'pos.access' => true,
                    'sales.create' => true,
                    'returns.create' => true,
                    'reports.view' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Anbardar',
                'slug' => 'anbar',
                'permissions' => json_encode([
                    'stock.view' => true,
                    'stock.manage' => true,
                    'pos.access' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('roles')->insert($roles);
    }
}
