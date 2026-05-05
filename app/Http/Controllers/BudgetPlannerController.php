<?php

namespace App\Http\Controllers;

use App\Models\BudgetPlanner;
use Illuminate\Http\Request;

class BudgetPlannerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'pemasukan'             => 'required|numeric',
            'pengeluaran_pokok'     => 'required|numeric',
            'pengeluaran_keinginan' => 'required|numeric',
            'tabungan_investasi'    => 'required|numeric',
            'bulan'                 => 'required|string',
            'tahun'                 => 'required|integer',
        ]);

        $budget = BudgetPlanner::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'bulan'   => $request->bulan,
                'tahun'   => $request->tahun,
            ],
            [
                'pemasukan'             => $request->pemasukan,
                'pengeluaran_pokok'     => $request->pengeluaran_pokok,
                'pengeluaran_keinginan' => $request->pengeluaran_keinginan,
                'tabungan_investasi'    => $request->tabungan_investasi,
            ]
        );

        // Cek spending alert 95%
        $total = $request->pengeluaran_pokok + $request->pengeluaran_keinginan;
        $persen = ($total / $request->pemasukan) * 100;
        $alert = $persen >= 95;

        return response()->json([
            'message'       => 'Budget planner berhasil disimpan',
            'budget'        => $budget,
            'persen_terpakai' => round($persen, 1),
            'spending_alert'  => $alert,
        ]);
    }

    public function show(Request $request)
    {
        $budget = BudgetPlanner::where('user_id', $request->user()->id)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        return response()->json($budget);
    }
}