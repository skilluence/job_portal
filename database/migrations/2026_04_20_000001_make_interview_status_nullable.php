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
        Schema::table('interviews', function (Blueprint $table) {
            // Using raw DB statements for PostgreSQL enum column compatibility
        });

        DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status DROP NOT NULL");
        DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status DROP DEFAULT");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE interviews SET interview_status = 'valid' WHERE interview_status IS NULL");
        DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status SET DEFAULT 'valid'");
        DB::statement("ALTER TABLE interviews ALTER COLUMN interview_status SET NOT NULL");
    }
};
