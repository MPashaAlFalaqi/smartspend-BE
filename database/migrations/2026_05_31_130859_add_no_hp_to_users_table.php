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
        Schema::table('users', function (Blueprint $col) {
            // Menambahkan kolom no_hp setelah kolom email, bertipe string, dan boleh kosong (nullable)
            $col->string('no_hp')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $col) {
            // Menghapus kembali kolom jika dilakukan rollback
            $col->dropColumn('no_hp');
        });
    }
};