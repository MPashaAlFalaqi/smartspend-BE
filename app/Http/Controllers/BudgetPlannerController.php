<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetPlanner;
use App\Models\Transaction; // <-- Impor model Transaction
use App\Models\User;        // Impor model User untuk update profil risiko
use Illuminate\Support\Facades\Auth;

class BudgetPlannerController extends Controller
{
    /**
     * Fungsi Baru: Menyimpan hasil Profil Risiko Akhir langsung ke data User
     * Endpoint rute: POST /api/final-analyze/save
     */
    public function saveFinalAnalyze(Request $request)
    {
        // 1. Validasi request dari frontend (FinalAnalyze.jsx)
        $validated = $request->validate([
            'total_pemasukan'   => 'required|numeric',
            'budget_pokok'      => 'required|numeric',
            'budget_keinginan'  => 'required|numeric',
            'budget_tabungan'   => 'required|numeric',
            'risk_profile'      => 'required|string', // Menangkap 'Konservatif', 'Moderat', atau 'Overspending'
        ]);

        // 2. Ambil user yang sedang login menggunakan token Bearer
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login tidak valid atau token kedaluwarsa.'
            ], 401);
        }

        // 3. Update kolom risk_profile milik user di tabel users
        // Menggunakan instance model User agar langsung tersimpan ke database
        $userData = User::find($user->id);
        $userData->risk_profile = $validated['risk_profile'];
        $userData->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil risiko berhasil disinkronkan ke database admin!',
            'risk_profile_saved' => $userData->risk_profile
        ], 200);
    }

    public function store(Request $request)
    {
        // 1. Validasi data input murni dari frontend (ditambahkan validasi untuk array rincian)
        $validated = $request->validate([
            'pemasukan'             => 'required|numeric',
            'pengeluaran_pokok'     => 'required|numeric',
            'pengeluaran_keinginan' => 'required|numeric',
            'tabungan_investasi'    => 'required|numeric',
            'bulan'                 => 'required|string',
            'tahun'                 => 'required|integer',
            'detail_pokok'          => 'nullable|array',
            'detail_keinginan'      => 'nullable|array',
            'detail_tabungan'       => 'nullable|array',
        ]);

        // 2. Ambil data diri user dari tabel risk_profiles
        $user = Auth::user();
        $profile = $user ? $user->riskProfile : null; 

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan isi data diri di Risk Profile terlebih dahulu sebelum menyusun anggaran.'
            ], 400);
        }

        // 3. LOGIKA OTOMATIS: Penentuan kategori risiko
        $penghasilanUser = $profile->penghasilan;
        
        if ($penghasilanUser >= 10000000) {
            $kategoriRisiko = 'agresif';
        } elseif ($penghasilanUser >= 5000000) {
            $kategoriRisiko = 'moderat';
        } else {
            $kategoriRisiko = 'konservatif';
        }

        // 4. LOGIKA OTOMATIS: Membuat pesan analisis
        if ($kategoriRisiko === 'agresif') {
            $pesanAnalisis = 'Profil Anda agresif. Anda memiliki ruang lebih untuk alokasi tabungan dan investasi berisiko tinggi.';
        } elseif ($kategoriRisiko === 'moderat') {
            $pesanAnalisis = 'Profil Anda moderat. Alokasi keuangan Anda cukup seimbang antara pengeluaran pokok dan masa depan.';
        } else {
            $pesanAnalisis = 'Profil Anda konservatif. Sebaiknya fokus pada dana darurat dan pengeluaran pokok yang aman.';
        }

        // 5. Simpan atau Update ke database Budget Planner
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
                'kategori_risiko'       => $kategoriRisiko, 
                'pesan_analisis'        => $pesanAnalisis,  
            ]
        );

        // =========================================================================
        // 6. OTOMATISASI KE HISTORY TRANSAKSI (REVISI INPUT MULTIPLE DETAILS)
        // =========================================================================

        $bulanTahun = $validated['bulan'] . ' ' . $validated['tahun'];

        // HAPUS DATA LAMA: Agar saat user update budget, data lama di history tidak menumpuk/duplikat
        Transaction::where('user_id', $user->id)
            ->whereIn('kategori', [
                'Pengeluaran Pokok (' . $bulanTahun . ')',
                'Pengeluaran Keinginan (' . $bulanTahun . ')',
                'Tabungan & Investasi (' . $bulanTahun . ')'
            ])->delete();

        // A. Masukkan Rincian Pengeluaran Pokok ke History
        if (!empty($request->detail_pokok) && is_array($request->detail_pokok)) {
            foreach ($request->detail_pokok as $item) {
                Transaction::create([
                    'user_id'  => $user->id,
                    'kategori' => 'Pengeluaran Pokok (' . $bulanTahun . ')',
                    'nama'     => $item['nama'] ?? 'Rincian Pokok',
                    'jumlah'   => $item['jumlah'] ?? 0,
                    'tipe'     => 'pengeluaran',
                    'tanggal'  => now(),
                ]);
            }
        } else {
            // Fallback jika frontend tidak mengirimkan array rincian
            Transaction::create([
                'user_id'  => $user->id,
                'kategori' => 'Pengeluaran Pokok (' . $bulanTahun . ')',
                'nama'     => 'Anggaran Kebutuhan Pokok',
                'jumlah'   => $validated['pengeluaran_pokok'],
                'tipe'     => 'pengeluaran',
                'tanggal'  => now(),
            ]);
        }

        // B. Masukkan Rincian Pengeluaran Keinginan ke History
        if (!empty($request->detail_keinginan) && is_array($request->detail_keinginan)) {
            foreach ($request->detail_keinginan as $item) {
                Transaction::create([
                    'user_id'  => $user->id,
                    'kategori' => 'Pengeluaran Keinginan (' . $bulanTahun . ')',
                    'nama'     => $item['nama'] ?? 'Rincian Keinginan',
                    'jumlah'   => $item['jumlah'] ?? 0,
                    'tipe'     => 'pengeluaran',
                    'tanggal'  => now(),
                ]);
            }
        } else {
            Transaction::create([
                'user_id'  => $user->id,
                'kategori' => 'Pengeluaran Keinginan (' . $bulanTahun . ')',
                'nama'     => 'Anggaran Kebutuhan Keinginan',
                'jumlah'   => $validated['pengeluaran_keinginan'],
                'tipe'     => 'pengeluaran',
                'tanggal'  => now(),
            ]);
        }

        // C. Masukkan Rincian Tabungan ke History
        if (!empty($request->detail_tabungan) && is_array($request->detail_tabungan)) {
            foreach ($request->detail_tabungan as $item) {
                Transaction::create([
                    'user_id'  => $user->id,
                    'kategori' => 'Tabungan & Investasi (' . $bulanTahun . ')',
                    'nama'     => $item['nama'] ?? 'Rincian Alokasi Tabungan',
                    'jumlah'   => $item['jumlah'] ?? 0,
                    'tipe'     => 'tabungan',
                    'tanggal'  => now(),
                ]);
            }
        } else {
            Transaction::create([
                'user_id'  => $user->id,
                'kategori' => 'Tabungan & Investasi (' . $bulanTahun . ')',
                'nama'     => 'Alokasi Tabungan',
                'jumlah'   => $validated['tabungan_investasi'],
                'tipe'     => 'tabungan',
                'tanggal'  => now(),
            ]);
        }

        // =========================================================================

        // 7. Hitung spending alert
        $totalPengeluaran = $validated['pengeluaran_pokok'] + $validated['pengeluaran_keinginan'];
        $persenTerpakai = ($totalPengeluaran / $validated['pemasukan']) * 100;
        $spendingAlert = $persenTerpakai >= 95;

        return response()->json([
            'success'         => true,
            'message'         => 'Data Budget Planner berhasil dihitung dan disimpan ke History!',
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
        
        $pemasukanTerbaru = $profile ? (float)$profile->penghasilan : 4000000;

        // 2. Ambil rekaman data budget planner terakhir milik user ini
        $budget = BudgetPlanner::where('user_id', $userId)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        // 3. JIKA BELUM PERNAH ADA BUDGET PLANNER
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

        // 4. JIKA REKAMAN ANGGARAN ADA
        $budget->pemasukan = $pemasukanTerbaru;

        return response()->json($budget);
    }
}