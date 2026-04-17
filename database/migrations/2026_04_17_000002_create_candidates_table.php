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
            $table->string('full_name');
            $table->date('enrollment_date')->nullable();
            $table->string('sales_agent')->nullable();
            $table->integer('no_of_applications')->default(0);
            $table->enum('status', ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold', 'inactive'])
                ->default('active');
            $table->foreignId('recruiter_id')->constrained('users');

            // LinkedIn & Email Details
            $table->string('linkedin_id')->nullable();
            $table->string('linkedin_password')->nullable();
            $table->string('email_id')->unique();
            $table->string('email_password')->nullable();
            $table->date('linkedin_updated')->nullable();

            // Extra info
            $table->text('address')->nullable();
            $table->text('profile')->nullable();
            $table->text('notes')->nullable();
            $table->string('login_password');
            $table->text('login_password_plain')->nullable();
            $table->string('cv_file_path')->nullable();
            $table->string('candidate_details_file_path')->nullable();

            // Interview tracking (will be replaced by interviews table later)
            $table->integer('interviews_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
