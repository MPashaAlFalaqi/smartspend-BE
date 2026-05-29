<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'nama',
        'jumlah',   // Sesuai dengan database kamu
        'tipe',     // Sesuai dengan database kamu
        'kategori',
        'tanggal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}