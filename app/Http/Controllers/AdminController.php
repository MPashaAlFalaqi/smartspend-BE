<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Lihat semua pengguna
    public function getAllUsers(Request $request)
    {
        $query = User::where('role', 'user');

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nama', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        // Filter status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($users);
    }

    // Lihat detail user
    public function getUser($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // Tambah user baru
    public function createUser(Request $request)
    {
        $request->validate([
            'nama'     => 'required|string',
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

    // Edit user
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama'   => 'string',
            'email'  => 'email|unique:users,email,'.$id,
            'status' => 'in:aktif,nonaktif',
        ]);

        $user->update([
            'nama'   => $request->nama ?? $user->nama,
            'email'  => $request->email ?? $user->email,
            'status' => $request->status ?? $user->status,
        ]);

        // Update password kalau diisi
        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return response()->json([
            'message' => 'Data pengguna berhasil diupdate',
            'user'    => $user,
        ]);
    }

    // Hapus user
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus'
        ]);
    }

    // Nonaktifkan / Aktifkan akun
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        $newStatus = $user->status === 'aktif' ? 'nonaktif' : 'aktif';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Status pengguna berhasil diubah',
            'status'  => $newStatus,
            'user'    => $user,
        ]);
    }

    // Laporan & Statistik
    public function getReports()
    {
        $totalUsers       = User::where('role', 'user')->count();
        $activeUsers      = User::where('role', 'user')->where('status', 'aktif')->count();
        $nonActiveUsers   = User::where('role', 'user')->where('status', 'nonaktif')->count();
        $newUsersThisWeek = User::where('role', 'user')
                               ->where('created_at', '>=', now()->subWeek())
                               ->count();
        $newUsersThisMonth = User::where('role', 'user')
                                ->where('created_at', '>=', now()->subMonth())
                                ->count();

        return response()->json([
            'total_users'         => $totalUsers,
            'active_users'        => $activeUsers,
            'non_active_users'    => $nonActiveUsers,
            'new_users_this_week' => $newUsersThisWeek,
            'new_users_this_month'=> $newUsersThisMonth,
        ]);
    }

    // Data pertumbuhan pengguna per bulan
    public function getUserGrowth()
    {
        $growth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = User::where('role', 'user')
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->count();
            $growth[] = [
                'bulan' => $month->format('M'),
                'total' => $count,
            ];
        }

        return response()->json($growth);
    }
}