<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Mengambil riwayat transaksi (Fungsi index)
     */
    public function index(Request $request)
    {
        // 1. Ambil user ID yang sedang login via Sanctum
        $userId = auth()->id() ?? 8; 

        $query = Transaction::where('user_id', $userId);

        // 2. Filter Berdasarkan Tanggal/Bulan Menyesuaikan Fitur Kalender React
        if ($request->has('tanggal') && $request->tanggal != '') {
            $date = Carbon::parse($request->tanggal);

            if ($request->get('mode') === 'day') {
                // Jika user memilih tanggal spesifik di kalender
                $query->whereDate('tanggal', $date->format('Y-m-d'));
            } else {
                // Jika user melihat satu bulan penuh
                $query->whereMonth('tanggal', $date->month)
                      ->whereYear('tanggal', $date->year);
            }
        }

        // 3. Filter berdasarkan Tipe (Semua / Pemasukan / Pengeluaran)
        if ($request->has('tipe') && $request->tipe != 'Semua') {
            $query->where('tipe', strtolower($request->tipe));
        }

        // 4. Filter berdasarkan Kolom Pencarian (Nama atau Kategori)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                  ->orWhere('kategori', 'like', '%' . $search . '%');
            });
        }

        // 5. Urutkan dari transaksi terbaru
        $transactions = $query->orderBy('tanggal', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Data riwayat transaksi berhasil diambil',
            'data' => $transactions
        ], 200);
    }

    /**
     * Menyimpan transaksi manual
     */
    public function store(Request $request)
    {
        try {
            $userId = auth()->id() ?? 8;

            $transaction = Transaction::create([
                'user_id' => $userId,
                'nama' => $request->nama,
                'tipe' => $request->tipe,
                'kategori' => $request->kategori,
                'jumlah' => $request->jumlah,
                'tanggal' => $request->tanggal ?? now()->format('Y-m-d'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil ditambahkan',
                'data' => $transaction
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus transaksi berdasarkan ID
     */
    public function destroy($id)
    {
        try {
            $userId = auth()->id() ?? 8;
            $transaction = Transaction::where('id', $id)->where('user_id', $userId)->first();

            if (!$transaction) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $transaction->delete();
            return response()->json(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ===== FITUR BARU: SINKRONISASI DASHBOARD & FINAL ANALYZE (DB-BASED) =====
    // =========================================================================

    /**
     * Menerima dan Menyimpan Hasil Hitungan Final dari Halaman Final Analyze
     */
    public function saveFinalAnalyze(Request $request) 
    {
        try {
            $userId = auth()->id() ?? 8;

            // Simpan data alokasi kalkulasi ke dalam tabel 'final_analyzes'
            DB::table('final_analyzes')->insert([
                'user_id' => $userId,
                'total_pemasukan' => $request->total_pemasukan,
                'budget_pokok' => $request->budget_pokok,
                'budget_keinginan' => $request->budget_keinginan,
                'budget_tabungan' => $request->budget_tabungan,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Analisis akhir keuangan berhasil disimpan ke sistem!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menyimpan analisis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengirim Data Analisis Terakhir ke Dashboard
     */
    public function getDashboardData()
    {
        try {
            $userId = auth()->id() ?? 8;

            // Ambil satu baris data analisis paling terbaru milik user ini
            $latestAnalysis = DB::table('final_analyzes')
                ->where('user_id', $userId)
                ->latest('created_at')
                ->first();

            // KONDISI JIKALAU USER BELUM PERNAH ANALISIS: Return struktur kosong (Rp 0)
            if (!$latestAnalysis) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_pemasukan' => 0,
                        'ringkasan_pengeluaran' => [
                            ['kategori' => 'Pokok', 'total' => 0],
                            ['kategori' => 'Keinginan', 'total' => 0],
                            ['kategori' => 'Tabungan', 'total' => 0],
                        ]
                    ]
                ], 200);
            }

            // KONDISI JIKA ADA DATA ANALISIS: Distribusikan datanya secara dinamis
            return response()->json([
                'success' => true,
                'data' => [
                    'total_pemasukan' => (int)$latestAnalysis->total_pemasukan,
                    'ringkasan_pengeluaran' => [
                        ['kategori' => 'Pokok', 'total' => (int)$latestAnalysis->budget_pokok],
                        ['kategori' => 'Keinginan', 'total' => (int)$latestAnalysis->budget_keinginan],
                        ['kategori' => 'Tabungan', 'total' => (int)$latestAnalysis->budget_tabungan],
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Gagal memuat data ringkasan dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}