<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetPlanner extends Model
{
    use HasFactory;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}