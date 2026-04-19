<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if already added by the updated create_candidates_table migration.
        if (Schema::hasColumn('candidates', 'interview_status')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->string('interview_status', 30)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('interview_status');
        });
    }
};
