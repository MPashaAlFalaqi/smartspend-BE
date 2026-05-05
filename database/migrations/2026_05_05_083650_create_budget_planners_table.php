<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_planners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('pemasukan', 15, 2);
            $table->decimal('pengeluaran_pokok', 15, 2)->default(0);
            $table->decimal('pengeluaran_keinginan', 15, 2)->default(0);
            $table->decimal('tabungan_investasi', 15, 2)->default(0);
            $table->string('bulan');
            $table->integer('tahun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_planners');
    }
};