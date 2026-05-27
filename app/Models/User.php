<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable; // <-- 1. TAMBAHKAN IMPORT INI

class User extends Authenticatable
{
    use HasApiTokens, Notifiable; // <-- 2. SEMENTARA DI SINI TAMBAHKAN Notifiable

    protected $fillable = [
        'nama',
        'email',
        'password',
        'google_id', 
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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