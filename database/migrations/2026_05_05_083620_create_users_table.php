<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Tetap pakai 'nama' sesuai database aslimu
            $table->string('username')->unique(); // <-- TAMBAHKAN INI (Wajib Unik)
            $table->string('email')->unique();
            $table->string('no_hp')->nullable(); // <-- TAMBAHKAN INI (Nullable agar Google Login yang gak punya No HP gak error)
            $table->string('password')->nullable(); // Diubah jadi nullable jika nanti pakai Google Login tanpa password manual
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema.dropIfExists('users');
    }
};