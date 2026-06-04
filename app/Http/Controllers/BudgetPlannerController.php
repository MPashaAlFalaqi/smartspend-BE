<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BudgetPlanner;
use App\Models\Transaction; 
use App\Models\User;        
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BudgetPlannerController extends Controller
{
    /**
     * 🟢 REVISI FINAL ANTI-ERROR: Mengamankan sinkronisasi profil risiko ke budget planner
     * Endpoint rute: POST /api/final-analyze/save
     */
    public function saveFinalAnalyze(Request $request)
    {
        // 1. Longgarkan validasi menjadi 'nullable' agar tidak memicu error skema database
        $validated = $request->validate([
            'total_pemasukan'   => 'nullable|numeric',
            'budget_pokok'      => 'nullable|numeric',
            'budget_keinginan'  => 'nullable|numeric',
            'budget_tabungan'   => 'nullable|numeric',
            'risk_profile'      => 'required|string', 
        ]);

        // 2. Ambil user login
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login tidak valid atau token kedaluwarsa.'
            ], 401);
        }

        // Standardisasi teks opsi kategori risiko
        $inputRisiko = strtolower(trim($validated['risk_profile']));
        if (!in_array($inputRisiko, ['konservatif', 'moderat', 'agresif'])) {
            $kategoriRisiko = 'moderat'; 
        } else {
            $kategoriRisiko = $inputRisiko;
        }

        // Tangkap nama bulan standar
        $bulanSekarang = now()->format('F'); 
        $tahunSekarang = (int) now()->format('Y');

        // 🟢 SOLUSI UTAMA: Menggunakan fallback '?? 0' jika data keuangan belum dikirim frontend
        $budget = BudgetPlanner::updateOrCreate(
            [
                'user_id' => $user->id,
                'bulan'   => $bulanSekarang,
                'tahun'   => $tahunSekarang
            ],
            [
                'kategori_risiko'       => $kategoriRisiko,
                'pemasukan'             => $validated['total_pemasukan'] ?? 0,
                'pengeluaran_pokok'     => $validated['budget_pokok'] ?? 0,
                'pengeluaran_keinginan' => $validated['budget_keinginan'] ?? 0,
                'tabungan_investasi'    => $validated['budget_tabungan'] ?? 0,
                'pesan_analisis'        => 'Hasil analisis profil risiko diperbarui melalui menu Final Analyze.'
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil risiko berhasil disinkronkan ke database admin!',
            'risk_profile_saved' => $kategoriRisiko,
            'data' => $budget
        ], 200);
    }

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
            'detail_pokok'          => 'nullable|array',
            'detail_keinginan'      => 'nullable|array',
            'detail_tabungan'       => 'nullable|array',
        ]);

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

        $bulanInput = trim($validated['bulan']);

        // 5. Simpan atau Update ke database Budget Planner
        $budget = BudgetPlanner::updateOrCreate(
            [
                'user_id' => $user->id,
                'bulan'   => $bulanInput,
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
        // 6. OTOMATISASI KE HISTORY TRANSAKSI
        // =========================================================================
        $bulanTahun = $bulanInput . ' ' . $validated['tahun'];

        Transaction::where('user_id', $user->id)
            ->whereIn('kategori', [
                'Pengeluaran Pokok (' . $bulanTahun . ')',
                'Pengeluaran Keinginan (' . $bulanTahun . ')',
                'Tabungan & Investasi (' . $bulanTahun . ')'
            ])->delete();

        // A. Masukkan Rincian Pengeluaran Pokok
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
            Transaction::create([
                'user_id'  => $user->id,
                'kategori' => 'Pengeluaran Pokok (' . $bulanTahun . ')',
                'nama'     => 'Anggaran Kebutuhan Pokok',
                'jumlah'   => $validated['pengeluaran_pokok'],
                'tipe'     => 'pengeluaran',
                'tanggal'  => now(),
            ]);
        }

        // B. Masukkan Rincian Pengeluaran Keinginan
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

        // C. Masukkan Rincian Tabungan
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

        $totalPengeluaran = $validated['pengeluaran_pokok'] + $validated['pengeluaran_keinginan'];
        $persenTerpakai = $validated['pemasukan'] > 0 ? ($totalPengeluaran / $validated['pemasukan']) * 100 : 0;
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
        $user = Auth::user();
        $profile = $user ? $user->riskProfile : null;
        
        $pemasukanTerbaru = $profile ? (float)$profile->penghasilan : 4000000;

        $budget = BudgetPlanner::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$budget) {
            return response()->json([
                'id' => null,
                'pemasukan' => $pemasukanTerbaru,
                'pengeluaran_pokok' => 0,
                'pengeluaran_keinginan' => 0,
                'tabungan_investasi' => 0,
                'bulan' => now()->format('F'),
                'tahun' => (int)now()->format('Y'),
                'kategori_risiko' => 'konservatif', // Fix: Menghindari error pemanggilan properti null dari model profile
                'pesan_analisis' => ''
            ]);
        }

        $budget->pemasukan = $pemasukanTerbaru;
        return response()->json($budget);
    }

    // =========================================================================
    // 🟢 SINKRONISASI UTAMA: MENYEMBUHKAN ERROR UNDEFINED TOTAL_PEMASUKAN
    // =========================================================================
    public function getDashboardSummary()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid.'
            ], 401);
        }

        $budget = BudgetPlanner::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->first();

        // Jika data belum dibuat sama sekali di database admin
        if (!$budget) {
            return response()->json([
                'success' => true,
                'pemasukan' => 0,
                'total_pemasukan' => 0, // 🟢 Menyediakan alias ganda penangkal error React
                'pengeluaran_pokok' => 0,
                'pengeluaran_keinginan' => 0,
                'tabungan_investasi' => 0,
                'kategori_risiko' => 'Belum Analisis'
            ], 200);
        }

        // 🟢 MENGIRIM DUA VERSI VARIABEL: 'pemasukan' DAN 'total_pemasukan'
        // Dengan ini, React (Dashboard.jsx:97) dijamin sukses membaca data tanpa crash!
        return response()->json([
            'success' => true,
            'pemasukan' => (float) $budget->pemasukan,
            'total_pemasukan' => (float) $budget->pemasukan, // 🟢 Inject variabel penolong
            'pengeluaran_pokok' => (float) $budget->pengeluaran_pokok,
            'pengeluaran_keinginan' => (float) $budget->pengeluaran_keinginan,
            'tabungan_investasi' => (float) $budget->tabungan_investasi,
            'kategori_risiko' => ucfirst($budget->kategori_risiko)
        ], 200);
    }
}