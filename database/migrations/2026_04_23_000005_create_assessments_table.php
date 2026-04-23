<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessment_date')->nullable();
            $table->time('assessment_time')->nullable();
            $table->string('role', 200);
            $table->string('company_name', 200);
            $table->string('domain', 200);
            $table->string('company_website_url', 500)->nullable();
            $table->date('mail_date')->nullable();
            $table->time('mail_time')->nullable();
            $table->enum('assessment_type', ['technical', 'screening', 'ai_interview', 'questions', 'virtual_video_interview']);
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
