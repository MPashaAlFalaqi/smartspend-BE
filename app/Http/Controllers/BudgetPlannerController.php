<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetPlanner;
use Illuminate\Support\Facades\Auth;

class BudgetPlannerController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi data input murni dari frontend
        $validated = $request->validate([
            'pemasukan'             => 'required|numeric',
            'pengeluaran_pokok'     => 'required|numeric',
            'pengeluaran_keinginan' => 'required|numeric',
            'tabungan_investasi'    => 'required|numeric',
            'bulan'                 => 'required|string',
            'tahun'                 => 'required|integer',
        ]);

        // 2. Ambil data diri user dari tabel risk_profiles
        $user = Auth::user();
        $profile = $user ? $user->riskProfile : null; // Mengambil relasi ke risk profile

        // Antisipasi jika user belum isi data diri/risk-profile sama sekali
        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan isi data diri di Risk Profile terlebih dahulu sebelum menyusun anggaran.'
            ], 400);
        }

        // 3. LOGIKA OTOMATIS: Penentuan kategori risiko berdasarkan penghasilan di risk profile
        $penghasilanUser = $profile->penghasilan;
        
        if ($penghasilanUser >= 10000000) {
            $kategoriRisiko = 'agresif';
        } elseif ($penghasilanUser >= 5000000) {
            $kategoriRisiko = 'moderat';
        } else {
            $kategoriRisiko = 'konservatif';
        }

        // 4. LOGIKA OTOMATIS: Membuat pesan analisis berdasarkan kategori risiko
        if ($kategoriRisiko === 'agresif') {
            $pesanAnalisis = 'Profil Anda agresif. Anda memiliki ruang lebih untuk alokasi tabungan dan investasi berisiko tinggi.';
        } elseif ($kategoriRisiko === 'moderat') {
            $pesanAnalisis = 'Profil Anda moderat. Alokasi keuangan Anda cukup seimbang antara pengeluaran pokok dan masa depan.';
        } else {
            $pesanAnalisis = 'Profil Anda konservatif. Sebaiknya fokus pada dana darurat dan pengeluaran pokok yang aman.';
        }

        // 5. Simpan atau Update ke database menggunakan updateOrCreate agar tidak duplikat di bulan yang sama
        $budget = BudgetPlanner::updateOrCreate(
            [
                'user_id' => $user->id,
                'bulan'   => $validated['bulan'],
                'tahun'   => $validated['tahun'],
            ],
            [
                'pemasukan'             => $validated['pemasukan'],
                'pengeluaran_pokok'     => $validated['pengeluaran_pokok'],
                'pengeluaran_keinginan' => $validated['pengeluaran_keinginan'],
                'tabungan_investasi'    => $validated['tabungan_investasi'],
                'kategori_risiko'       => $kategoriRisiko, // Terisi otomatis dari logika sistem
                'pesan_analisis'        => $pesanAnalisis,  // Terisi otomatis dari logika sistem
            ]
        );

        // 6. Hitung spending alert (tambahan info dari kodingan kamu sebelumnya)
        $totalPengeluaran = $validated['pengeluaran_pokok'] + $validated['pengeluaran_keinginan'];
        $persenTerpakai = ($totalPengeluaran / $validated['pemasukan']) * 100;
        $spendingAlert = $persenTerpakai >= 95;

        return response()->json([
            'success'         => true,
            'message'         => 'Data Budget Planner berhasil dihitung dan disimpan!',
            'data'            => $budget,
            'persen_terpakai' => round($persenTerpakai, 1),
            'spending_alert'  => $spendingAlert
        ], 201);
    }

    public function show()
    {
        $userId = Auth::id() ?? 8;

        // 1. Ambil data pemasukan terbaru dari tabel Risk Profile user
        $user = Auth::user();
        $profile = $user ? $user->riskProfile : null;
        
        // Ambil nominal penghasilan dari risk profile, jika belum ada gunakan fallback 4.000.000
        $pemasukanTerbaru = $profile ? (float)$profile->penghasilan : 4000000;

        // 2. Ambil rekaman data budget planner terakhir milik user ini
        $budget = BudgetPlanner::where('user_id', $userId)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        // 3. JIKA BELUM PERNAH ADA BUDGET PLANNER: Berikan response struktur awal dengan nominal dari Risk Profile
        if (!$budget) {
            return response()->json([
                'id' => null,
                'pemasukan' => $pemasukanTerbaru,
                'pengeluaran_pokok' => 0,
                'pengeluaran_keinginan' => 0,
                'tabungan_investasi' => 0,
                'bulan' => now()->format('F'),
                'tahun' => (int)now()->format('Y'),
                'kategori_risiko' => $profile ? $profile->kategori_risiko : 'konservatif',
                'pesan_analisis' => ''
            ]);
        }

        // 4. JIKA REKAMAN ANGGARAN ADA: Paksa kolom 'pemasukan' agar sinkron dengan data Risk Profile paling baru
        $budget->pemasukan = $pemasukanTerbaru;

        return response()->json($budget);
    }
}