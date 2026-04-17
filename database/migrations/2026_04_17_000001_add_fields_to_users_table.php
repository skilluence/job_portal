<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'recruiter', 'manager'])->default('admin')->after('email');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('role');
            $table->foreignId('team_manager_id')->nullable()->after('status')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_manager_id');
            $table->dropColumn(['role', 'status']);
        });
    }
};
