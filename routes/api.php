<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RiskProfileController;
use App\Http\Controllers\BudgetPlannerController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\AdminController; 
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 🔓 PUBLIC ROUTES (Akses Tanpa Login / Bebas Token)
// ==========================================
Route::post('/register',       [AuthController::class, 'register']);
Route::post('/login',          [AuthController::class, 'login']);
Route::post('/auth/google',    [AuthController::class, 'googleLogin']);
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');


// ==========================================
// 🔓 BYPASS ADMIN ROUTES (Area Publik Bebas Hambatan)
// ==========================================
Route::prefix('admin')->group(function () {
    Route::get('/dashboard-data', [AdminController::class, 'getDashboardData']);
    Route::get('/dashboard',      [AdminController::class, 'getDashboardData']); 
    Route::get('/users',          [AdminController::class, 'getAllUsers']);
    
    // Akses manipulasi data pengguna via panel admin
    Route::post('/users',         [AdminController::class, 'createUser']);
    Route::put('/users/{id}',     [AdminController::class, 'updateUser']);
    Route::delete('/users/{id}',  [AdminController::class, 'deleteUser']);
    Route::patch('/users/{id}/toggle', [AdminController::class, 'toggleStatus']);
});


// ==========================================
// 🔒 PROTECTED ROUTES (Wajib Login via Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // --- Auth & Profile User ---
    Route::post('/logout',               [AuthController::class, 'logout']);
    Route::get('/me',                    [AuthController::class, 'me']);
    Route::put('/user/update',           [AuthController::class, 'updateProfile']);
    Route::put('/user/update-password',  [AuthController::class, 'updatePassword']);
    
    // FITUR BARU: Rute hapus foto profil terikat dengan AuthController
    Route::post('/user/delete-photo',    [AuthController::class, 'deletePhoto']);

    // --- Risk Profile ---
    Route::post('/risk-profile', [RiskProfileController::class, 'store']);
    Route::get('/risk-profile',  [RiskProfileController::class, 'show']);

    // --- Budget Planner ---
    Route::post('/budget-planner', [BudgetPlannerController::class, 'store']);
    Route::get('/budget-planner',  [BudgetPlannerController::class, 'show']);

    // --- Sinkronisasi Dashboard & Final Analyze User ---
    Route::post('/final-analyze/save', [BudgetPlannerController::class, 'saveFinalAnalyze']);
    
    // 🟢 FIXED: Diarahkan ke fungsi getDashboardSummary di BudgetPlannerController agar sinkron otomatis
    Route::get('/dashboard-summary',   [BudgetPlannerController::class, 'getDashboardSummary']);

    // --- Transactions History ---
    Route::get('/transactions',        [TransactionController::class, 'index']);
    Route::post('/transactions',       [TransactionController::class, 'store']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    // ==========================================
    // 👑 PROTECTED ADMIN ROUTES (Wajib Token Admin Tambahan)
    // ==========================================
    Route::prefix('admin')->group(function () {

        // --- Manage Users (CRUD Pengguna) ---
        Route::get('/users/{id}',          [AdminController::class, 'getUser']);

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