<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add team_manager_id to candidates table
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('team_manager_id')
                ->nullable()
                ->after('recruiter_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Create candidate_resumes table
        Schema::create('candidate_resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('designation', 200);
            $table->string('file_path');
            $table->string('original_filename', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_resumes');

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['team_manager_id']);
            $table->dropColumn('team_manager_id');
        });
    }
};
