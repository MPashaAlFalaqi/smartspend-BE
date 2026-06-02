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
        // Cek dulu biar aman, kalau belum ada kolom risk_profile baru kita buat
        if (!Schema::hasColumn('users', 'risk_profile')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('risk_profile')->nullable()->after('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'risk_profile')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('risk_profile');
            });
        }
    }
};