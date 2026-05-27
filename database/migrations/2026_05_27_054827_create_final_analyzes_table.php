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
        Schema::create('final_analyzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Menghubungkan ke ID user yang login
            $table->bigInteger('total_pemasukan')->default(0); // Menampung total pemasukan
            $table->bigInteger('budget_pokok')->default(0);    // Menampung pengeluaran pokok
            $table->bigInteger('budget_keinginan')->default(0); // Menampung pengeluaran keinginan
            $table->bigInteger('budget_tabungan')->default(0);  // Menampung tabungan/investasi
            $table->timestamps();

            // Opsional: Bagus untuk relasi database agar aman
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_analyzes');
    }
};