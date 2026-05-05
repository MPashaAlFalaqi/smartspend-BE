<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPlanner extends Model
{
    protected $fillable = [
        'user_id',
        'pemasukan',
        'pengeluaran_pokok',
        'pengeluaran_keinginan',
        'tabungan_investasi',
        'bulan',
        'tahun',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}