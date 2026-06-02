<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

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
    // 👥 2. FUNGSI KELOLA DATA USER (MANAGE USERS) - PURE REALTIME
    // ==========================================
    public function getAllUsers(Request $request)
    {
        try {
            // Ambil query pencarian jika ada dari React frontend (?search=...)
            $search = $request->query('search');

            $query = User::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', '%' . $search . '%')
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            // Ambil data user secara real-time murni apa adanya dari database
            $users = $query->orderBy('id', 'desc')->get();

            // Transformasi data agar sesuai dengan format objek bertingkat React
            $dataUsers = $users->map(function ($user, $index) {
                
                $dbValue = $user->risk_profile;

                // Jika kolom di database kosong atau null, pasang teks "Belum Mengisi"
                if (empty($dbValue)) {
                    $riskStructure = [
                        'kategori_risiko' => 'Belum Mengisi'
                    ];
                } else {
                    // Jika ada isinya, ambil data asli secara dinamis sesuai baris user masing-masing
                    $riskStructure = [
                        'kategori_risiko' => $dbValue 
                    ];
                }

                return [
                    'no'            => $index + 1,
                    'id'            => $user->id,
                    'nama'          => $user->nama ?? ($user->name ?? 'Pengguna'), 
                    'name'          => $user->nama ?? ($user->name ?? 'Pengguna'), 
                    'email'         => $user->email,
                    'status'        => $user->status ?? 'aktif',
                    
                    // Dikirim dalam bentuk objek: item.risk_profile.kategori_risiko
                    'risk_profile'  => $riskStructure,
                ];
            })->all();

            return response()->json($dataUsers, 200);

        } catch (Exception $e) {
            return response()->json([
                'error'   => 'Gagal memuat data pengguna',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}