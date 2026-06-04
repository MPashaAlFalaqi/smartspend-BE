<?php

namespace App\Http\Controllers;

use App\Models\RiskProfile;
use App\Models\Transaction; 
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class RiskProfileController extends Controller
{
    public function store(Request $request)
    {
        try {
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

            // Jika user tidak ditemukan, hentikan proses
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'error'  => 'User tidak terotentikasi dengan benar. Silakan log out lalu log in kembali.'
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

            // ==========================================
            // 2. AMBIL STATUS RISIKO AWAL (SINKRON DB REAL)
            // ==========================================
            // 🟢 PERBAIKAN: Ditambahkan $request->input('profil_risiko') agar sinkron dengan payload dari React
            $inputRisiko = $request->input('profil_risiko') ?? $request->input('risk_profile') ?? $request->input('kategori_risiko') ?? 'konservatif';
            $inputRisiko = strtolower(trim($inputRisiko));

            // Validasi ENUM sesuai kolom di phpMyAdmin
            if (in_array($inputRisiko, ['konservatif', 'moderat', 'agresif'])) {
                $kategoriRisiko = $inputRisiko;
            } else {
                $kategoriRisiko = 'konservatif'; 
            }

            // 🟢 SOLUSI UTAMA: Menggunakan format 'F' (English) agar sinkron dengan Dashboard Controller
            $bulanSekarang = now()->format('F'); 
            $tahunSekarang = (int) now()->format('Y');

            // 🟢 SINKRONISASI TOTAL: Menyertakan fallback default value '0' agar terbebas dari jeratan SQL State 1364
            DB::table('budget_planners')->updateOrInsert(
                [
                    'user_id'         => $userId,
                    'bulan'           => $bulanSekarang,
                    'tahun'           => $tahunSekarang
                ],
                [
                    'kategori_risiko'       => $kategoriRisiko,
                    'pemasukan'             => $request->penghasilan,  
                    'pengeluaran_pokok'     => 0, // Fallback aman anti-error 1364
                    'pengeluaran_keinginan' => 0, // Fallback aman anti-error 1364
                    'tabungan_investasi'    => 0, // Fallback aman anti-error 1364
                    'pesan_analisis'        => 'Profil risiko awal berhasil dibuat.',
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]
            );

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
                'status'       => 'success',
                'message'      => 'Data diri berhasil disimpan',
                'profile'      => $profile,
                'risk_profile' => $kategoriRisiko
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan pada internal server database.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json((object)[]);
            }

            $profile = RiskProfile::where('user_id', $user->id)->first();
            return response()->json($profile ?? (object)[]);
            
        } catch (Exception $e) {
            return response()->json((object)[]);
        }
    }
}