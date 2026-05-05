<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'nama',
        'email',
        'password',
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