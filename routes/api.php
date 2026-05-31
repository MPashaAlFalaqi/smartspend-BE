<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RiskProfileController;
use App\Http\Controllers\BudgetPlannerController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\AdminController; // 👈 SUDAH DIPERBAIKI: Menuju ke sub-folder API sesuai struktur folder Anda

// ==========================================
// 🔓 PUBLIC ROUTES (Akses Tanpa Login / Bebas Token)
// ==========================================
Route::post('/register',       [AuthController::class, 'register']);
Route::post('/login',          [AuthController::class, 'login']);
Route::post('/auth/google',    [AuthController::class, 'googleLogin']);
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

// 🧪 JALUR BYPASS SEMENTARA: Mengeluarkan dashboard dari auth agar React bisa langsung menarik data tanpa error 401
Route::get('/admin/dashboard', [AdminController::class, 'getDashboardData']);


// ==========================================
// 🔒 PROTECTED ROUTES (Wajib Login via Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --- Auth & Profile User ---
    // Jika auth login Anda masih error Class "App\Models\User" not found, pastikan model User.php menggunakan `namespace App\Models;`
    Route::post('/logout',   [AuthController::class, 'logout']);
    Route::get('/me',        [AuthController::class, 'me']);
    Route::put('/user/update',          [AuthController::class, 'updateProfile']);
    Route::put('/user/update-password', [AuthController::class, 'updatePassword']);

    // --- Risk Profile ---
    Route::post('/risk-profile', [RiskProfileController::class, 'store']);
    Route::get('/risk-profile',  [RiskProfileController::class, 'show']);

    // --- Budget Planner ---
    Route::post('/budget-planner', [BudgetPlannerController::class, 'store']);
    Route::get('/budget-planner',  [BudgetPlannerController::class, 'show']);

    // --- Sinkronisasi Dashboard & Final Analyze User ---
    Route::post('/final-analyze/save', [TransactionController::class, 'saveFinalAnalyze']);
    Route::get('/dashboard-summary',   [TransactionController::class, 'getDashboardData']);

    // --- Transactions History ---
    Route::get('/transactions',          [TransactionController::class, 'index']);
    Route::post('/transactions',         [TransactionController::class, 'store']);
    Route::delete('/transactions/{id}',  [TransactionController::class, 'destroy']);

    // ==========================================
    // 👑 ADMIN ROUTES (Prefix: /api/admin/...)
    // ==========================================
    Route::prefix('admin')->group(function () {

        // ⚠️ Rute /dashboard sudah dipindahkan sementara ke area PUBLIC di atas demi mempermudah pengetesan

        // --- Manage Users (CRUD Pengguna) ---
        Route::get('/users',               [AdminController::class, 'getAllUsers']);
        Route::get('/users/{id}',          [AdminController::class, 'getUser']);
        Route::post('/users',              [AdminController::class, 'createUser']);
        Route::put('/users/{id}',          [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}',       [AdminController::class, 'deleteUser']);
        Route::patch('/users/{id}/toggle', [AdminController::class, 'toggleStatus']);

        // --- Manage Admins (CRUD Akses Sesama Admin) ---
        Route::get('/admins',         [AdminController::class, 'getAllAdmins']);
        Route::post('/admins',        [AdminController::class, 'createAdmin']);
        Route::put('/admins/{id}',    [AdminController::class, 'updateAdmin']);
        Route::delete('/admins/{id}', [AdminController::class, 'deleteAdmin']);

        // --- Reports & Analitik ---
        Route::get('/reports',          [AdminController::class, 'getReports']);
        Route::get('/reports/growth',   [AdminController::class, 'getUserGrowth']);
        Route::get('/reports/activity', [AdminController::class, 'getRecentActivity']);
    });

});