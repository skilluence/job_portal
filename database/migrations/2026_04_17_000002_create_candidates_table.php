<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();

            // ── Name ──────────────────────────────────────────
            $table->string('full_name');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();

            // ── Personal ──────────────────────────────────────
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('email_id')->unique();
            $table->string('phone_number')->nullable();

            // ── Domain ────────────────────────────────────────
            $table->string('domain')->nullable();
            $table->text('sub_domain')->nullable();

            // ── Sensitive (encrypted at app layer) ────────────
            $table->text('ssn')->nullable();
            $table->date('date_of_arrival_usa')->nullable();

            // ── Salary ────────────────────────────────────────
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();

            // ── Address ───────────────────────────────────────
            $table->string('street_address')->nullable();
            $table->string('apartment_unit')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();

            // ── Visa ──────────────────────────────────────────
            $table->string('visa_immigration_status')->nullable();
            $table->string('work_auth_status')->nullable();
            $table->boolean('open_to_relocation')->default(false);
            $table->string('preferred_city')->nullable();
            $table->date('visa_expiry_date')->nullable();

            // ── Marketing ─────────────────────────────────────
            $table->string('marketing_phone')->nullable();
            $table->string('marketing_email')->nullable();
            $table->text('marketing_email_password')->nullable();   // encrypted
            $table->string('marketing_linkedin_id')->nullable();
            $table->text('marketing_linkedin_password')->nullable(); // encrypted

            // ── Education — Master's ──────────────────────────
            $table->string('masters_university')->nullable();
            $table->string('masters_program')->nullable();
            $table->date('masters_start')->nullable();
            $table->date('masters_end')->nullable();
            $table->string('masters_country')->nullable();

            // ── Education — Bachelor's ────────────────────────
            $table->string('bachelors_university')->nullable();
            $table->string('bachelors_program')->nullable();
            $table->date('bachelors_start')->nullable();
            $table->date('bachelors_end')->nullable();
            $table->string('bachelors_country')->nullable();

            // ── Professional / Online Presence ────────────────
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('speedy_apply_json_path')->nullable();
            $table->text('recruiter_notes')->nullable();

            // ── Portal / Admin ────────────────────────────────
            $table->integer('no_of_applications')->default(0);
            $table->integer('interviews_count')->default(0);
            $table->enum('status', [
                'active', 'enrolled', 'interview', 'offer', 'placed', 'onhold', 'inactive',
            ])->default('active');
            $table->string('interview_status')->nullable();

            // ── Files ─────────────────────────────────────────
            $table->string('cv_file_path')->nullable();
            $table->string('candidate_details_file_path')->nullable();

            // ── Student Portal Login ───────────────────────────
            $table->string('login_password')->nullable();
            $table->text('login_password_plain')->nullable(); // encrypted

            // ── Relations ─────────────────────────────────────
            $table->foreignId('recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // ── Legacy / backward-compat ──────────────────────
            $table->date('enrollment_date')->nullable();
            $table->string('sales_agent')->nullable();
            $table->string('linkedin_id')->nullable();
            $table->text('linkedin_password')->nullable();
            $table->string('email_password')->nullable();
            $table->date('linkedin_updated')->nullable();
            $table->text('address')->nullable();
            $table->text('profile')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
