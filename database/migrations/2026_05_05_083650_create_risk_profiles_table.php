<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('usia');
            $table->string('pekerjaan');
            $table->enum('status', ['mahasiswa','pekerja','wiraswasta','pensiunan']);
            $table->decimal('penghasilan', 15, 2);
            $table->enum('kategori', ['konservatif','moderat','agresif']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_profiles');
    }
};