<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 200);
            $table->string('company_name', 200);
            $table->string('company_domain', 500);
            $table->date('mail_date')->nullable();
            $table->time('mail_time')->nullable();
            $table->enum('interview_type', ['phone_call', 'virtual', 'on_site']);
            $table->enum('interview_status', ['valid', 'invalid'])->default('valid');
            $table->text('remark')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->string('scheduled_timezone', 50)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
