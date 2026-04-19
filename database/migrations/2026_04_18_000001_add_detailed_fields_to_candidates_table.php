<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If the updated create_candidates_table migration already added these columns, skip.
        if (Schema::hasColumn('candidates', 'first_name')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table) {
            // Personal Info
            $table->string('first_name', 100)->nullable()->after('full_name');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_name');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('gender', 30)->nullable()->after('date_of_birth');
            $table->string('nationality', 100)->nullable()->after('gender');
            $table->string('phone_number', 30)->nullable()->after('email_id');
            $table->string('domain', 150)->nullable();
            $table->string('sub_domain', 150)->nullable();
            $table->text('ssn')->nullable();
            $table->date('date_of_arrival_usa')->nullable();
            $table->decimal('current_salary', 12, 2)->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();

            // Address
            $table->string('street_address')->nullable();
            $table->string('apartment_unit', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_province', 5)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->nullable()->default('United States');

            // Visa & Work Authorization
            $table->string('visa_immigration_status', 50)->nullable();
            $table->string('work_auth_status', 50)->nullable();
            $table->boolean('open_to_relocation')->nullable();
            $table->string('preferred_city', 100)->nullable();
            $table->date('visa_expiry_date')->nullable();

            // Marketing Contact
            $table->string('marketing_phone', 30)->nullable();
            $table->string('marketing_email')->nullable();
            $table->text('marketing_email_password')->nullable();
            $table->string('marketing_linkedin_id')->nullable();
            $table->text('marketing_linkedin_password')->nullable();

            // Education — Masters
            $table->string('masters_university')->nullable();
            $table->string('masters_program', 200)->nullable();
            $table->date('masters_start')->nullable();
            $table->date('masters_end')->nullable();
            $table->string('masters_country', 100)->nullable();

            // Education — Bachelors
            $table->string('bachelors_university')->nullable();
            $table->string('bachelors_program', 200)->nullable();
            $table->date('bachelors_start')->nullable();
            $table->date('bachelors_end')->nullable();
            $table->string('bachelors_country', 100)->nullable();

            // Professional Profile
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('speedy_apply_json_path')->nullable();
            $table->text('recruiter_notes')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender',
                'nationality', 'phone_number', 'domain', 'sub_domain', 'ssn',
                'date_of_arrival_usa', 'current_salary', 'expected_salary',
                'street_address', 'apartment_unit', 'city', 'state_province', 'zip_code', 'country',
                'visa_immigration_status', 'work_auth_status', 'open_to_relocation',
                'preferred_city', 'visa_expiry_date',
                'marketing_phone', 'marketing_email', 'marketing_email_password',
                'marketing_linkedin_id', 'marketing_linkedin_password',
                'masters_university', 'masters_program', 'masters_start', 'masters_end', 'masters_country',
                'bachelors_university', 'bachelors_program', 'bachelors_start', 'bachelors_end', 'bachelors_country',
                'github_url', 'linkedin_url', 'speedy_apply_json_path', 'recruiter_notes',
            ]);

        });
    }
};
