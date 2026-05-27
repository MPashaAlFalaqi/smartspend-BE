<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetPlanner;
use Illuminate\Support\Facades\Auth;

class BudgetPlannerController extends Controller
{
    public function store(Request $request)
    {
        // Validasi data input dari frontend sesuai kolom Model kamu
        $validated = $request->validate([
            'pemasukan'             => 'required|numeric',
            'pengeluaran_pokok'     => 'required|numeric',
            'pengeluaran_keinginan' => 'required|numeric',
            'tabungan_investasi'    => 'required|numeric',
            'bulan'                 => 'required|string',
            'tahun'                 => 'required|integer',
        ]);

        // Simpan ke database terikat dengan ID user yang sedang login
        $budget = Auth::user()->budgetPlanners()->create([
            'pemasukan'             => $validated['pemasukan'],
            'pengeluaran_pokok'     => $validated['pengeluaran_pokok'],
            'pengeluaran_keinginan' => $validated['pengeluaran_keinginan'],
            'tabungan_investasi'    => $validated['tabungan_investasi'],
            'bulan'                 => $validated['bulan'],
            'tahun'                 => $validated['tahun'],
            'kategori_risiko', // Tambahkan ini
    'pesan_analisis'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Budget Planner berhasil disimpan!',
            'data'    => $budget
        ], 201);
    }
}