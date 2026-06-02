<?php

namespace App\Http\Controllers;

use App\Models\RiskProfile;
use App\Models\Transaction; 
use App\Models\User; 
use Illuminate\Http\Request;

class RiskProfileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'usia'        => 'required|integer',
            'pekerjaan'   => 'required|string',
            'penghasilan' => 'required|numeric',
        ]);

        // 🔥 PROTEKSI GANDA: Cek data email dari frontend
        $frontendEmail = $request->input('email'); 
        
        if (!empty($frontendEmail)) {
            $user = User::where('email', $frontendEmail)->first();
        } else {
            $user = $request->user();
        }

        // Jika user tidak ditemukan, hentikan proses agar data tidak menimpa user lain
        if (!$user) {
            return response()->json([
                'error' => 'User tidak terotentikasi dengan benar. Silakan log out lalu log in kembali.'
            ], 401);
        }

        $userId = $user->id;

        // 1. Menyimpan data murni Risk Profile ke tabel 'risk_profiles'
        $profile = RiskProfile::updateOrCreate(
            ['user_id' => $userId],
            [
                'usia'        => $request->usia,
                'pekerjaan'   => $request->pekerjaan,
                'penghasilan' => $request->penghasilan,
            ]
        );

        // 2. AMBIL STATUS RISIKO AWAL (Bawaan database lama atau default 'Belum Analisis')
        // Profil risiko yang sebenarnya baru akan di-update secara sah saat user menekan simpan di FinalAnalyze!
        $kategoriRisiko = $request->input('risk_profile') ?? $request->input('kategori_risiko') ?? $user->risk_profile ?? 'Belum Analisis';

        // Update tabel users tanpa menebak status secara paksa dari penghasilan
        User::where('id', $userId)->update([
            'risk_profile' => $kategoriRisiko
        ]);

        // 3. OTOMATISASI: Masuk ke history transaksi sebagai Pemasukan
        Transaction::updateOrCreate(
            [
                'user_id'  => $userId,
                'kategori' => 'Pendapatan (Risk Profile)', 
            ],
            [
                'nama'     => 'Saldo Awal / Pemasukan Bulanan',
                'jumlah'   => $request->penghasilan,
                'tipe'     => 'pemasukan', 
                'tanggal'  => now(), 
            ]
        );

        return response()->json([
            'message'      => 'Data diri berhasil disimpan',
            'profile'      => $profile,
            'risk_profile' => $kategoriRisiko
        ]);
    }

    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json((object)[]);
        }

        $profile = RiskProfile::where('user_id', $user->id)->first();
        return response()->json($profile ?? (object)[]);
    }
}