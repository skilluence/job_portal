<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Keep production/server data safe.
        // Demo data is seeded only on local.
        if (!app()->environment('local')) {
            return;
        }

        $this->call(LocalDemoSeeder::class);
    }
}
