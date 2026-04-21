<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status DROP NOT NULL");
            DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status DROP DEFAULT");
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE interviews MODIFY COLUMN interview_status ENUM('valid','invalid') NULL DEFAULT NULL");
            return;
        }

        // SQLite and other drivers: rely on schema change() fallback.
        Schema::table('interviews', function (Blueprint $table) {
            $table->enum('interview_status', ['valid', 'invalid'])->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        DB::statement("UPDATE interviews SET interview_status = 'valid' WHERE interview_status IS NULL");

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status SET DEFAULT 'valid'");
            DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status SET NOT NULL");
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE interviews MODIFY COLUMN interview_status ENUM('valid','invalid') NOT NULL DEFAULT 'valid'");
            return;
        }

        Schema::table('interviews', function (Blueprint $table) {
            $table->enum('interview_status', ['valid', 'invalid'])->default('valid')->nullable(false)->change();
        });
    }
};
