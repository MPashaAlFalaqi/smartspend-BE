<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RiskProfileController;
use App\Http\Controllers\BudgetPlannerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AdminController;

// ===== PUBLIC ROUTES =====
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ===== USER ROUTES =====
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',   [AuthController::class, 'logout']);
    Route::get('/me',        [AuthController::class, 'me']);
    Route::put('/profile',   [AuthController::class, 'updateProfile']);

    Route::post('/risk-profile', [RiskProfileController::class, 'store']);
    Route::get('/risk-profile',  [RiskProfileController::class, 'show']);

    Route::post('/budget-planner', [BudgetPlannerController::class, 'store']);
    Route::get('/budget-planner',  [BudgetPlannerController::class, 'show']);

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