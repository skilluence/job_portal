<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('candidates', 'state_province')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE candidates MODIFY state_province VARCHAR(100) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE candidates ALTER COLUMN state_province TYPE VARCHAR(100)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('candidates', 'state_province')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE candidates MODIFY state_province VARCHAR(5) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE candidates ALTER COLUMN state_province TYPE VARCHAR(5)');
        }
    }
};
