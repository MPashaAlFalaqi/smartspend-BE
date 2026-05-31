<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable; 
use Illuminate\Database\Eloquent\Factories\HasFactory; // Tambahan aman untuk factory jika dibutuhkan

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'username',      
        'email',
        'password',
        'no_hp',         
        'tanggal_lahir', 
        'kota',          
        'jenis_kelamin', 
        'google_id', 
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Memastikan password otomatis di-hash dengan aman oleh Laravel
    ];

    // ==========================================
    // 🔗 RELASI DATABASE (MENGGUNAKAN ABSOLUTE PATH)
    // ==========================================

    /**
     * Relasi ke model RiskProfile (One-to-One)
     */
    public function riskProfile()
    {
        return $this->hasOne(\App\Models\RiskProfile::class);
    }

    /**
     * Relasi ke model BudgetPlanner (One-to-Many)
     */
    public function budgetPlanners()
    {
        return $this->hasMany(\App\Models\BudgetPlanner::class);
    }

    /**
     * Relasi ke model Transaction (One-to-Many)
     */
    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class);
    }
}