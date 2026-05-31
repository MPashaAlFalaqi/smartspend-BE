<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // ===== CEK ROLE ADMIN (AMBLES PROOF / ANTI CRASH NULL) =====
    private function checkAdmin(Request $request)
    {
        // Pastikan user sudah login terlebih dahulu sebelum membaca property 'role'
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak! Hanya admin yang bisa mengakses.'
            ], 403)->send(); 
            exit; // Hentikan eksekusi script agar tidak lanjut ke query bawah
        }
    }

    // ===== 👑 DATA UNTUK HALAMAN UTAMA DASHBOARD =====
    public function getDashboardData(Request $request)
    {
        $this->checkAdmin($request);

        // 1. Ambil data statistik user (Menggunakan logika getReports bawaan)
        $totalUsers        = User::where('role', 'user')->count();
        $activeUsers       = User::where('role', 'user')->where('status', 'aktif')->count();
        $nonActiveUsers    = User::where('role', 'user')->where('status', 'nonaktif')->count();
        $newUsersThisWeek  = User::where('role', 'user')->where('created_at', '>=', now()->subWeek())->count();

        // 2. Ambil total baris riil dari tabel transactions di database
        $totalTransactions = DB::table('transactions')->count();

        // 3. Ambil 5 aktivitas pendaftar user paling segar (Sinkron dengan tabel di React)
        $activities = User::where('role', 'user')
            ->orderBy('created_at', 'desc') // Menggunakan created_at agar pendaftar baru naik ke baris paling atas
            ->take(5)
            ->get()
            ->map(function($user) {
                // Generasi inisial avatar yang dinamis dan rapi (Maksimal 2 Huruf Kapital)
                $words = explode(' ', trim($user->nama));
                $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

                return [
                    'avatar' => $initials ?: 'US',
                    'nama'   => $user->nama,
                    'email'  => $user->email,
                    'aksi'   => 'Registrasi akun baru', // Diubah agar logis dengan aksi pendaftaran user baru
                    'waktu'  => $user->created_at ? $user->created_at->diffForHumans() : '-',
                    'status' => 'sukses', // Default sukses karena data sudah berhasil masuk database
                ];
            });

        return response()->json([
            'total_users'         => $totalUsers,
            'active_users'        => $activeUsers,
            'non_active_users'    => $nonActiveUsers,
            'new_users_this_week' => $newUsersThisWeek,
            'total_transactions'  => $totalTransactions,
            'activities'          => $activities
        ], 200);
    }

    // ===== LIHAT SEMUA PENGGUNA =====
    public function getAllUsers(Request $request)
    {
        $this->checkAdmin($request);

        $query = User::with('riskProfile')->where('role', 'user');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nama', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($users, 200);
    }

    // ===== LIHAT DETAIL USER =====
    public function getUser(Request $request, $id)
    {
        $this->checkAdmin($request);

        $user = User::with('riskProfile')->findOrFail($id);

        return response()->json($user, 200);
    }

    // ===== TAMBAH USER BARU =====
    public function createUser(Request $request)
    {
        $this->checkAdmin($request);

        $request->validate([
            'nama'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
            'status'   => 'in:aktif,nonaktif',
        ]);

        $usernameFromEmail = explode('@', $request->email)[0] . rand(10, 99);

        $user = User::create([
            'nama'     => $request->nama,
            'email'    => $request->email,
            'username' => $usernameFromEmail,
            'password' => Hash::make($request->password),
            'role'     => 'user',
            'status'   => $request->status ?? 'aktif',
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan',
            'user'    => $user,
        ], 201);
    }

    // ===== EDIT USER =====
    public function updateUser(Request $request, $id)
    {
        $this->checkAdmin($request);

        $user = User::findOrFail($id);

        $request->validate([
            'nama'   => 'string|max:255',
            'email'  => 'email|unique:users,email,'.$id,
            'status' => 'in:aktif,nonaktif',
        ]);

        $updateData = [];

        if ($request->nama)   $updateData['nama']   = $request->nama;
        if ($request->email)  $updateData['email']  = $request->email;
        if ($request->status) $updateData['status'] = $request->status;
        if ($request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Data pengguna berhasil diupdate',
            'user'    => $user->fresh(),
        ], 200);
    }

    // ===== HAPUS USER =====
    public function deleteUser(Request $request, $id)
    {
        $this->checkAdmin($request);

        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun admin!'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus'
        ], 200);
    }

    // ===== TOGGLE STATUS AKTIF/NONAKTIF =====
    public function toggleStatus(Request $request, $id)
    {
        $this->checkAdmin($request);

        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Tidak bisa mengubah status akun admin!'
            ], 403);
        }

        $newStatus = $user->status === 'aktif' ? 'nonaktif' : 'aktif';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Status pengguna berhasil diubah menjadi '.$newStatus,
            'status'  => $newStatus,
            'user'    => $user->fresh(),
        ], 200);
    }

    // ===== LAPORAN & STATISTIK (UNTUK HALAMAN ANALITIK) =====
    public function getReports(Request $request)
    {
        $this->checkAdmin($request);

        $totalUsers        = User::where('role', 'user')->count();
        $activeUsers       = User::where('role', 'user')->where('status', 'aktif')->count();
        $nonActiveUsers    = User::where('role', 'user')->where('status', 'nonaktif')->count();
        
        $newUsersThisWeek  = User::where('role', 'user')->where('created_at', '>=', now()->subWeek())->count();
        $newUsersThisMonth = User::where('role', 'user')->where('created_at', '>=', now()->subMonth())->count();

        $riskDistribution = DB::table('risk_profiles')
            ->select('kategori_risiko', DB::raw('count(*) as total'))
            ->groupBy('kategori_risiko')
            ->get();

        return response()->json([
            'total_users'          => $totalUsers,
            'active_users'         => $activeUsers,
            'non_active_users'     => $nonActiveUsers,
            'new_users_this_week'  => $newUsersThisWeek,
            'new_users_this_month' => $newUsersThisMonth,
            'risk_distribution'    => $riskDistribution
        ], 200);
    }

    // ===== PERTUMBUHAN PENGGUNA PER BULAN =====
    public function getUserGrowth(Request $request)
    {
        $this->checkAdmin($request);

        $growth = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = User::where('role', 'user')
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->count();

            $growth[] = [
                'bulan' => $month->format('M Y'),
                'total' => $count,
            ];
        }

        return response()->json($growth, 200);
    }

    // ===== AKTIVITAS TERBARU (UNTUK HALAMAN LOG TERPISAH) =====
    public function getRecentActivity(Request $request)
    {
        $this->checkAdmin($request);

        $users = User::where('role', 'user')
                    ->orderBy('updated_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function($user) {
                        $words = explode(' ', trim($user->nama));
                        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

                        return [
                            'avatar' => $initials ?: 'US',
                            'nama'   => $user->nama,
                            'email'  => $user->email,
                            'aksi'   => 'Update Data Profil',
                            'waktu'  => $user->updated_at ? $user->updated_at->diffForHumans() : '-',
                            'status' => $user->status === 'aktif' ? 'sukses' : 'gagal',
                        ];
                    });

        return response()->json($users, 200);
    }

    // ===== CRUD KHUSUS SESAMA ADMIN =====

    // 1. LIHAT SEMUA ADMIN
    public function getAllAdmins(Request $request)
    {
        $this->checkAdmin($request);
        $admins = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        return response()->json($admins, 200);
    }

    // 2. DAFTARKAN ADMIN BARU
    public function createAdmin(Request $request)
    {
        $this->checkAdmin($request);

        $request->validate([
            'nama'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $usernameFromEmail = explode('@', $request->email)[0] . rand(10, 99);

        $admin = User::create([
            'nama'     => $request->nama,
            'email'    => $request->email,
            'username' => $usernameFromEmail,
            'password' => Hash::make($request->password),
            'role'     => 'admin',
            'status'   => 'aktif',
        ]);

        return response()->json([
            'message' => 'Admin baru berhasil didaftarkan',
            'admin'   => $admin,
        ], 201);
    }

    // 3. UPDATE DATA ADMIN
    public function updateAdmin(Request $request, $id)
    {
        $this->checkAdmin($request);
        $admin = User::where('id', $id)->where('role', 'admin')->firstOrFail();

        $request->validate([
            'nama'  => 'string|max:255',
            'email' => 'email|unique:users,email,'.$id,
        ]);

        $updateData = [];
        if ($request->nama)     $updateData['nama'] = $request->nama;
        if ($request->email)    $updateData['email'] = $request->email;
        if ($request->password) $updateData['password'] = Hash::make($request->password);

        $admin->update($updateData);

        return response()->json([
            'message' => 'Data admin berhasil diperbarui',
            'admin'   => $admin->fresh()
        ], 200);
    }

    // 4. HAPUS AKSES ADMIN
    public function deleteAdmin(Request $request, $id)
    {
        $this->checkAdmin($request);

        if ($request->user()->id == $id) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun Anda sendiri!'], 400);
        }

        $admin = User::where('id', $id)->where('role', 'admin')->firstOrFail();
        $admin->delete();

        return response()->json(['message' => 'Akses admin berhasil dicabut'], 200);
    }
}