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
        Schema::table('risk_profiles', function (Blueprint $table) {
            // ❌ Perintah untuk menghapus kolom status
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risk_profiles', function (Blueprint $table) {
            // 🔄 Cadangan rollback jika migrasi dibatalkan
            $table->enum('status', ['mahasiswa', 'pekerja', 'wiraswasta', 'pensiunan'])->nullable();
        });
    }
};