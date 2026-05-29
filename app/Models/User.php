<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable; 

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; 

    // REVISI: Menambahkan kolom baru agar diizinkan masuk ke database
    protected $fillable = [
        'nama',
        'username',      // <-- Tambahan Baru
        'email',
        'password',
        'no_hp',         // <-- Tambahan Baru
        'tanggal_lahir', // <-- Tambahan Baru
        'kota',          // <-- Tambahan Baru
        'jenis_kelamin', // <-- Tambahan Baru
        'google_id', 
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relasi bawaan SmartSpend kamu tetap aman di sini
    public function riskProfile()
    {
        return $this->hasOne(RiskProfile::class);
    }

    public function budgetPlanners()
    {
        return $this->hasMany(BudgetPlanner::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}