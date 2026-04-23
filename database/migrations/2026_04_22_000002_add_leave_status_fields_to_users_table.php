<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('inactive_reason', 30)->nullable()->after('status');
            $table->timestamp('leave_override_until')->nullable()->after('inactive_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['inactive_reason', 'leave_override_until']);
        });
    }
};
