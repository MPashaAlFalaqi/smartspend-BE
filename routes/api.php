<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RiskProfileController;
use App\Http\Controllers\BudgetPlannerController;
use App\Http\Controllers\API\TransactionController; // Jalur impor sudah diperbarui ke sub-folder API
use App\Http\Controllers\AdminController;

// ===== PUBLIC ROUTES =====
Route::post('/register',       [AuthController::class, 'register']);
Route::post('/login',          [AuthController::class, 'login']);
Route::post('/auth/google',    [AuthController::class, 'googleLogin']);
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

// ===== USER ROUTES (PROTECTED VIA SANCTUM) =====
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',   [AuthController::class, 'logout']);
    Route::get('/me',        [AuthController::class, 'me']);
    
    // REVISI & TAMBAHAN: Disesuaikan dengan URL yang dipanggil oleh UserProfile.jsx
    Route::put('/user/update',          [AuthController::class, 'updateProfile']);
    Route::put('/user/update-password', [AuthController::class, 'updatePassword']);

    // Risk Profile
    Route::post('/risk-profile', [RiskProfileController::class, 'store']);
    Route::get('/risk-profile',  [RiskProfileController::class, 'show']);

    // Budget Planner
    Route::post('/budget-planner', [BudgetPlannerController::class, 'store']);
    Route::get('/budget-planner',  [BudgetPlannerController::class, 'show']);

    // ===== ROUTE SINKRONISASI DASHBOARD & FINAL ANALYZE =====
    Route::post('/final-analyze/save', [TransactionController::class, 'saveFinalAnalyze']);
    Route::get('/dashboard-summary',   [TransactionController::class, 'getDashboardData']);

    // Transactions History (Memanggil fungsi index)
    Route::get('/transactions',          [TransactionController::class, 'index']);
    Route::post('/transactions',         [TransactionController::class, 'store']);
    Route::delete('/transactions/{id}',  [TransactionController::class, 'destroy']);
});

// ===== ADMIN ROUTES =====
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // Manage Users
    Route::get('/users',               [AdminController::class, 'getAllUsers']);
    Route::get('/users/{id}',          [AdminController::class, 'getUser']);
    Route::post('/users',              [AdminController::class, 'createUser']);
    Route::put('/users/{id}',          [AdminController::class, 'updateUser']);
    Route::delete('/users/{id}',       [AdminController::class, 'deleteUser']);
    Route::patch('/users/{id}/toggle', [AdminController::class, 'toggleStatus']);

    // Reports
    Route::get('/reports',             [AdminController::class, 'getReports']);
    Route::get('/reports/growth',      [AdminController::class, 'getUserGrowth']);
    Route::get('/reports/activity',    [AdminController::class, 'getRecentActivity']);

});