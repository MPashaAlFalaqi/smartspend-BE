<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetPlanner extends Model
{
    use HasFactory;

    // 🟢 KUNCI PERBAIKAN: Definisikan nama tabel secara tegas sesuai di phpMyAdmin Anda
    protected $table = 'budget_planners';

    // Kolom yang diizinkan untuk pengisian massal (Mass Assignment)
    protected $fillable = [
        'user_id',
        'pemasukan',
        'pengeluaran_pokok',
        'pengeluaran_keinginan',
        'tabungan_investasi',
        'bulan',
        'tahun',
        'kategori_risiko',
        'pesan_analisis'
    ];

    /**
     * Relasi ke model User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}