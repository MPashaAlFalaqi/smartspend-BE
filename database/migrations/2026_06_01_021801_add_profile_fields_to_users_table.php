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
            // Menambahkan kolom-kolom profil yang kurang secara aman jika belum ada
            if (!Schema::hasColumn('users', 'tanggal_lahir')) {
                $table->string('tanggal_lahir')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'kota')) {
                $table->string('kota')->nullable()->after('tanggal_lahir');
            }
            if (!Schema::hasColumn('users', 'jenis_kelamin')) {
                $table->string('jenis_kelamin')->default('Laki-laki')->after('kota');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->longText('avatar')->nullable()->after('jenis_kelamin'); // longText aman untuk nampung string Base64 foto
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tanggal_lahir', 'kota', 'jenis_kelamin', 'avatar']);
        });
    }
};