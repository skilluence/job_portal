<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('candidates', 'login_password')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->string('login_password')->nullable()->after('notes');
            });
        }

        if (!Schema::hasColumn('candidates', 'login_password_plain')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->text('login_password_plain')->nullable()->after('login_password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('candidates', 'login_password_plain')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropColumn('login_password_plain');
            });
        }

        if (Schema::hasColumn('candidates', 'login_password')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropColumn('login_password');
            });
        }
    }
};
