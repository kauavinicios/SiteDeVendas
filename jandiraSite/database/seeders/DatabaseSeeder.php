<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'teste@gmail.com',
                'name' => 'Administrador',
                'role' => UserRole::Admin,
                'marketing_opt_in' => false,
                'marketing_opt_in_at' => null,
            ],
            [
                'email' => 'gerente@gmail.com',
                'name' => 'Gerente',
                'role' => UserRole::Manager,
                'marketing_opt_in' => false,
                'marketing_opt_in_at' => null,
            ],
            [
                'email' => 'lojista@gmail.com',
                'name' => 'Lojista',
                'role' => UserRole::Storekeeper,
                'marketing_opt_in' => false,
                'marketing_opt_in_at' => null,
            ],
            [
                'email' => 'suporte@gmail.com',
                'name' => 'Suporte',
                'role' => UserRole::Support,
                'marketing_opt_in' => false,
                'marketing_opt_in_at' => null,
            ],
            [
                'email' => 'cliente@gmail.com',
                'name' => 'Cliente Teste',
                'role' => UserRole::Customer,
                'marketing_opt_in' => true,
                'marketing_opt_in_at' => now(),
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('12345678'),
                    'role' => $data['role'],
                    'active' => true,
                    'marketing_opt_in' => $data['marketing_opt_in'],
                    'marketing_opt_in_at' => $data['marketing_opt_in_at'],
                ]
            );
        }

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
