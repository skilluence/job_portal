<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const DEFAULT_ADMIN_EMAIL = 'rahil@skilluence.com';
    private const DEFAULT_ADMIN_PASSWORD = 'Rahil@050725';

    public function run(): void
    {
        if (app()->environment('local')) {
            $this->call(LocalDemoSeeder::class);
        }

        $this->seedDefaultAdmin();
    }

    private function seedDefaultAdmin(): void
    {
        User::updateOrCreate(
            ['email' => self::DEFAULT_ADMIN_EMAIL],
            [
                'name' => 'Rahil Admin',
                'password' => Hash::make(self::DEFAULT_ADMIN_PASSWORD),
                'password_plain' => self::DEFAULT_ADMIN_PASSWORD,
                'role' => 'admin',
                'status' => 'active',
                'team_manager_id' => null,
            ]
        );
    }
}
