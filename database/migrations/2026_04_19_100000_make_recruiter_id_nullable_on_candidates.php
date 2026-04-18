<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['recruiter_id']);
            $table->foreignId('recruiter_id')->nullable()->change()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['recruiter_id']);
            $table->foreignId('recruiter_id')->nullable(false)->change()->constrained('users');
        });
    }
};
