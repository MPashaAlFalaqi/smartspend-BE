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
        Schema::table('users', function (Blueprint $table) {
            // 1. Menambahkan kolom google_id setelah kolom email dan bersifat opsional (nullable)
            $table->string('google_id')->nullable()->after('email')->unique();

            // 2. Mengubah kolom password bawaan agar boleh kosong (nullable)
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Mengembalikan ke struktur semula jika migrasi di-rollback
            $table->dropColumn('google_id');
            $table->string('password')->nullable(false)->change();
        });
    }
};