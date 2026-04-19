<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the one and only admin account.
     * On migrate:fresh this is the sole user created — no sign-up flow exists.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'rahil@skilluenc.com'],
            [
                'name'            => 'Rahil',
                'password'        => Hash::make('Rahil@050725'),
                'role'            => 'admin',
                'status'          => 'active',
                'team_manager_id' => null,
            ]
        );
    }
}
