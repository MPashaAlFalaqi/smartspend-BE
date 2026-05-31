<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB; // 👈 Impor DB Facade untuk query langsung

class AdminController extends Controller 
{
    // ==========================================
    // 📊 1. FUNGSI UTAMA DASHBOARD
    // ==========================================
    public function getDashboardData(Request $request)
    {
        try {
            $totalUsers = User::count() ?? 0;
            $activeUsers = User::where('status', 'aktif')->count() ?? 0;
            $nonActiveUsers = User::where('status', 'nonaktif')
                ->orWhere('status', 'ditangguhkan')
                ->count() ?? 0;
            $totalTransactions = Transaction::count() ?? 0;
            
            $recentUsers = User::orderBy('id', 'desc')->take(5)->get();
            $activities = [];
            
            if ($recentUsers->count() > 0) {
                foreach ($recentUsers as $user) {
                    $activities[] = [
                        'nama'   => $user->nama ?? $user->name,
                        'email'  => $user->email,
                        'aksi'   => 'Mendaftar sebagai pengguna baru',
                        'waktu'  => $user->created_at ? $user->created_at->diffForHumans() : 'Baru saja', 
                        'status' => 'sukses'
                    ];
                }
            } else {
                $activities[] = [
                    'nama'   => 'Sistem SmartSpend',
                    'email'  => 'system@smartspend.com',
                    'aksi'   => 'Belum ada aktivitas pendaftaran baru',
                    'waktu'  => '-',
                    'status' => 'sukses'
                ];
            }

            return response()->json([
                'total_users'         => (int) $totalUsers,
                'active_users'        => (int) $activeUsers,
                'non_active_users'    => (int) $nonActiveUsers,
                'new_users_this_week' => 0,
                'total_transactions'  => (int) $totalTransactions,
                'activities'          => $activities
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error'   => 'Gagal memuat data database',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // 👥 2. FUNGSI KELOLA DATA USER (MANAGE USERS)
    // ==========================================
    public function getAllUsers(Request $request)
    {
        try {
            // 1. Ambil semua data pengguna langsung dari tabel users
            $users = User::orderBy('id', 'desc')->get();

            // 2. Petakan format data agar sesuai dengan kolom tabel di React
            $dataUsers = $users->map(function ($user, $index) {
                
                // 🔥 JALUR BYPASS MANUAL: Cek langsung ke tabel risk_profiles pakai DB Facade
                // Langkah ini mencocokkan id user dengan kolom user_id secara langsung tanpa lewat relasi Eloquent
                $cekDataRisiko = DB::table('risk_profiles')
                    ->where('user_id', $user->id)
                    ->exists(); // Mengembalikan true jika data ada, false jika tidak ada

                // 🔄 Tentukan status teks berdasarkan hasil pengecekan database di atas
                if ($cekDataRisiko) {
                    $hasilRisiko = 'Sudah Mengisi';
                } else {
                    $hasilRisiko = 'Belum Mengisi';
                }

                return [
                    'no'            => $index + 1,
                    'id'            => $user->id,
                    'nama'          => $user->nama ?? $user->name, 
                    'email'         => $user->email,
                    'status'        => $user->status ?? 'aktif',
                    'profil_risiko' => $hasilRisiko, // 👈 Status "Sudah Mengisi" dijamin ter-update secara akurat!
                ];
            });

            return response()->json($dataUsers, 200);

        } catch (Exception $e) {
            return response()->json([
                'error'   => 'Gagal memuat data pengguna',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}