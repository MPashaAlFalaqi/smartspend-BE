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
    // 📊 1. FUNGSI UTAMA DASHBOARD & REPORTS
    // ==========================================
    public function getDashboardData(Request $request)
    {
        try {
            $totalUsers = User::count() ?? 0;
            $activeUsers = User::where('status', 'aktif')->count() ?? 0;
            
            // Grouping query status agar tidak merusak kalkulasi data agregat lainnya
            $nonActiveUsers = User::where(function($q) {
                $q->where('status', 'nonaktif')
                  ->orWhere('status', 'ditangguhkan');
            })->count() ?? 0;

            $totalTransactions = Transaction::count() ?? 0;
            
            // Tren pertumbuhan user 6 bulan terakhir untuk Chart
            $trenPertumbuhan = [];
            for ($i = 5; $i >= 0; $i--) {
                $bulanObj = now()->subMonths($i);
                $trenPertumbuhan[] = [
                    'bulan' => $bulanObj->isoFormat('MMM'),
                    'jumlah' => User::whereMonth('created_at', $bulanObj->month)
                                    ->whereYear('created_at', $bulanObj->year)
                                    ->count()
                ];
            }

            // Distribusi profil risiko dari tabel budget_planners sesuai ERD nyata
            $konservatif = DB::table('budget_planners')->where('kategori_risiko', 'konservatif')->count() ?? 0;
            $moderat = DB::table('budget_planners')->where('kategori_risiko', 'moderat')->count() ?? 0;
            $agresif = DB::table('budget_planners')->where('kategori_risiko', 'agresif')->count() ?? 0;

            $newUsersThisWeek = User::where('created_at', '>=', now()->startOfWeek())->count() ?? 0;
            $recentUsers = User::orderBy('id', 'desc')->take(5)->get();
            $activities = [];
            
            if ($recentUsers->count() > 0) {
                foreach ($recentUsers as $user) {
                    $activities[] = [
                        'nama'   => $user->nama ?? ($user->username ?? 'Pengguna Baru'),
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
                'status'              => 'success', 
                'total_users'         => (int) $totalUsers,
                'active_users'        => (int) $activeUsers,
                'non_active_users'    => (int) $nonActiveUsers,
                'new_users_this_week' => (int) $newUsersThisWeek,
                'total_transactions'  => (int) $totalTransactions,
                'tren_pertumbuhan'    => $trenPertumbuhan,
                'distribusi_risiko'   => [
                    'konservatif' => $konservatif,
                    'moderat'     => $moderat,
                    'agresif'     => $agresif,
                ],
                'activities'          => $activities
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
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
            $search = $request->query('search');
            $query = User::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', '%' . $search . '%')
                      ->orWhere('username', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            $users = $query->orderBy('id', 'desc')->get();

            $dataUsers = $users->map(function ($user, $index) {
                // Relasi manual ke budget_planners sesuai ERD
                $latestBudget = DB::table('budget_planners')
                                    ->where('user_id', $user->id)
                                    ->orderBy('id', 'desc')
                                    ->first();

                $dbValue = $latestBudget ? $latestBudget->kategori_risiko : null;
                $riskStructure = [
                    'kategori_risiko' => empty($dbValue) ? 'Belum Mengisi' : ucfirst($dbValue)
                ];

                return [
                    'no'            => $index + 1,
                    'id'            => $user->id,
                    'nama'          => $user->nama ?? ($user->username ?? 'Pengguna'), 
                    'name'          => $user->nama ?? ($user->username ?? 'Pengguna'), 
                    'email'         => $user->email,
                    'status'        => $user->status ?? 'aktif',
                    'risk_profile'  => $riskStructure,
                ];
            })->all();

            return response()->json($dataUsers, 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // ➕ 3. FUNGSI TAMBAH PENGGUNA BARU
    // ==========================================
    public function createUser(Request $request)
    {
        try {
            $request->validate([
                'nama' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'status' => 'required|in:aktif,nonaktif,ditangguhkan'
            ]);

            $user = User::create([
                'nama' => $request->nama,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'status' => $request->status ?? 'aktif',
                'role' => 'user'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengguna berhasil ditambahkan',
                'data' => $user
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // ✏️ 4. FUNGSI UPDATE DATA PENGGUNA
    // ==========================================
    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Pengguna tidak ditemukan'], 404);
            }

            $request->validate([
                'nama' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'status' => 'required|in:aktif,nonaktif,ditangguhkan'
            ]);

            $user->nama = $request->nama;
            $user->email = $request->email;
            $user->status = $request->status;

            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengguna berhasil diperbarui'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // 🗑️ 5. FUNGSI HAPUS PENGGUNA (FIXED SINKRONISASI METHOD)
    // ==========================================
    public function deleteUser($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pengguna tidak ditemukan'
                ], 404);
            }

            // Hapus data berelasi di tabel anak (Foreign Key) terlebih dahulu agar tidak memicu error Constraint DB
            DB::table('budget_planners')->where('user_id', $id)->delete();
            DB::table('risk_profiles')->where('user_id', $id)->delete();
            DB::table('transactions')->where('user_id', $id)->delete();
            DB::table('final_analyzes')->where('user_id', $id)->delete();

            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengguna berhasil dicabut secara permanen'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // 🚫 6. FUNGSI TOGGLE STATUS (BLOKIR / AKTIFKAN)
    // ==========================================
    public function toggleStatus($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Pengguna tidak ditemukan'], 404);
            }

            $user->status = ($user->status === 'aktif') ? 'nonaktif' : 'aktif';
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Status pengguna berhasil diubah menjadi ' . $user->status,
                'current_status' => $user->status
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}