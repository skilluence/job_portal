<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_assignment_histories', function (Blueprint $table) {
            $table->timestamp('temporary_starts_at')->nullable()->after('note');
            $table->timestamp('temporary_ends_at')->nullable()->after('temporary_starts_at');
            $table->timestamp('temporary_activated_at')->nullable()->after('temporary_ends_at');
            $table->timestamp('temporary_restored_at')->nullable()->after('temporary_activated_at');
            $table->foreignId('restore_recruiter_id')->nullable()->after('temporary_restored_at')->constrained('users')->nullOnDelete();
            $table->foreignId('restore_team_manager_id')->nullable()->after('restore_recruiter_id')->constrained('users')->nullOnDelete();

            $table->index(['action', 'temporary_starts_at', 'temporary_ends_at'], 'cah_temp_window_idx');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_assignment_histories', function (Blueprint $table) {
            $table->dropIndex('cah_temp_window_idx');
            $table->dropForeign(['restore_recruiter_id']);
            $table->dropForeign(['restore_team_manager_id']);
            $table->dropColumn([
                'temporary_starts_at',
                'temporary_ends_at',
                'temporary_activated_at',
                'temporary_restored_at',
                'restore_recruiter_id',
                'restore_team_manager_id',
            ]);
        });
    }
};
