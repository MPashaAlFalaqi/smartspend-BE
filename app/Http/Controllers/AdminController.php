<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // ===== CEK ROLE ADMIN =====
    private function checkAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, json_encode([
                'message' => 'Akses ditolak! Hanya admin yang bisa mengakses.'
            ]));
        }
    }

    // ===== LIHAT SEMUA PENGGUNA =====
    public function getAllUsers(Request $request)
    {
        $this->checkAdmin($request);

        $query = User::where('role', 'user');

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

        return response()->json($users);
    }

    // ===== LIHAT DETAIL USER =====
    public function getUser(Request $request, $id)
    {
        $this->checkAdmin($request);

        $user = User::findOrFail($id);

        return response()->json($user);
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

        $user = User::create([
            'nama'     => $request->nama,
            'email'    => $request->email,
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
        ]);
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
        ]);
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
        ]);
    }

    // ===== LAPORAN & STATISTIK =====
    public function getReports(Request $request)
    {
        $this->checkAdmin($request);

        $totalUsers        = User::where('role', 'user')->count();
        $activeUsers       = User::where('role', 'user')
                                 ->where('status', 'aktif')->count();
        $nonActiveUsers    = User::where('role', 'user')
                                 ->where('status', 'nonaktif')->count();
        $newUsersThisWeek  = User::where('role', 'user')
                                 ->where('created_at', '>=', now()->subWeek())
                                 ->count();
        $newUsersThisMonth = User::where('role', 'user')
                                 ->where('created_at', '>=', now()->subMonth())
                                 ->count();

        return response()->json([
            'total_users'          => $totalUsers,
            'active_users'         => $activeUsers,
            'non_active_users'     => $nonActiveUsers,
            'new_users_this_week'  => $newUsersThisWeek,
            'new_users_this_month' => $newUsersThisMonth,
        ]);
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

        return response()->json($growth);
    }

    // ===== AKTIVITAS TERBARU =====
    public function getRecentActivity(Request $request)
    {
        $this->checkAdmin($request);

        $users = User::where('role', 'user')
                    ->orderBy('updated_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function($user) {
                        return [
                            'nama'   => $user->nama,
                            'email'  => $user->email,
                            'aksi'   => 'Update Data',
                            'waktu'  => $user->updated_at->diffForHumans(),
                            'status' => $user->status === 'aktif' ? 'sukses' : 'nonaktif',
                        ];
                    });

        return response()->json($users);
    }
}