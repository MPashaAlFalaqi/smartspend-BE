<?php

namespace App\Http\Controllers;

use App\Models\RiskProfile;
use Illuminate\Http\Request;

class RiskProfileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'usia'        => 'required|integer',
            'pekerjaan'   => 'required|string',
            'status'      => 'required|in:mahasiswa,pekerja,wiraswasta,pensiun', // Ditambah validasi enum biar aman
            'penghasilan' => 'required|numeric',
        ]);

        // Menyimpan data murni tanpa menghitung kategori lagi
        $profile = RiskProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'usia'        => $request->usia,
                'pekerjaan'   => $request->pekerjaan,
                'status'      => $request->status,
                'penghasilan' => $request->penghasilan,
            ]
        );

        return response()->json([
            'message'  => 'Data diri berhasil disimpan',
            'profile'  => $profile,
        ]);
    }

    public function show(Request $request)
    {
        $profile = RiskProfile::where(
            'user_id', $request->user()->id
        )->first();

        // Kalau datanya kosong, kembalikan json kosong agar frontend aman
        return response()->json($profile ?? (object)[]);
    }
}