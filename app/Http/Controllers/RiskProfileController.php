<?php

namespace App\Http\Controllers;

use App\Models\RiskProfile;
use App\Models\Transaction; // <-- Impor model Transaction agar bisa digunakan
use Illuminate\Http\Request;

class RiskProfileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'usia'        => 'required|integer',
            'pekerjaan'   => 'required|string',
            'status'      => 'required|in:mahasiswa,pekerja,wiraswasta,pensiun', 
            'penghasilan' => 'required|numeric',
        ]);

        $userId = $request->user()->id;

        // 1. Menyimpan data murni Risk Profile
        $profile = RiskProfile::updateOrCreate(
            ['user_id' => $userId],
            [
                'usia'        => $request->usia,
                'pekerjaan'   => $request->pekerjaan,
                'status'      => $request->status,
                'penghasilan' => $request->penghasilan,
            ]
        );

        // 2. OTOMATISASI: Masuk ke history sebagai Pemasukan
        // REVISI: Menggunakan kolom 'nama', 'jumlah', dan 'tipe' sesuai struktur DB kamu
        Transaction::updateOrCreate(
            [
                'user_id'  => $userId,
                'kategori' => 'Pendapatan (Risk Profile)', // Kunci pencarian biar ga double
            ],
            [
                'nama'     => 'Saldo Awal / Pemasukan Bulanan',
                'jumlah'   => $request->penghasilan,
                'tipe'     => 'pemasukan', // Masuk ke kartu TOTAL PEMASUKAN di history
                'tanggal'  => now(), // Tanggal transaksi hari ini
            ]
        );

        return response()->json([
            'message'  => 'Data diri dan saldo awal berhasil disimpan',
            'profile'  => $profile,
        ]);
    }

    public function show(Request $request)
    {
        $profile = RiskProfile::where(
            'user_id', $request->user()->id
        )->first();

        return response()->json($profile ?? (object)[]);
    }
}