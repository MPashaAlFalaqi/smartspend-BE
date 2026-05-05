<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskProfile extends Model
{
    protected $fillable = [
        'user_id',
        'usia',
        'pekerjaan',
        'status',
        'penghasilan',
        'kategori',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}