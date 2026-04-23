<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('from_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('from_team_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_team_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'created_at']);
            $table->index(['changed_by', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_assignment_histories');
    }
};
