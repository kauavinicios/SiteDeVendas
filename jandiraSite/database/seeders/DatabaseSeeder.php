<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\LegalDocuments;
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
            $user = User::firstOrNew(['email' => $data['email']]);

            $user->forceFill([
                'name' => $data['name'],
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'role' => $data['role'],
                'active' => true,
                'marketing_opt_in' => $data['marketing_opt_in'],
                'marketing_opt_in_at' => $data['marketing_opt_in_at'],
                'terms_accepted_at' => now(),
                'terms_version' => LegalDocuments::TERMS_VERSION,
                'privacy_accepted_at' => now(),
                'privacy_version' => LegalDocuments::PRIVACY_VERSION,
            ])->save();
        }

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
