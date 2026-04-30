<?php

use Illuminate\Support\Facades\Route;
use App\Http\controllers\haiController;
use App\Http\controllers\KategoriBukuController;


Route::get('/', function () {
    return view('welcome');
});
Route::get('/hai', [haiController::class, 'index']);
Route::get('/kategori-buku',[KategoriBukuController::class,'index']);
Route::get('/kategori-buku.tambah',[KategoriBukuController::class,'create']);
Route::post('/kategori-buku.data',[KategoriBukuController::class,'store']);