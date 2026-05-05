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
            'status'      => 'required',
            'penghasilan' => 'required|numeric',
        ]);

        $kategori = $this->tentukanKategori(
            $request->usia,
            $request->penghasilan
        );

        $profile = RiskProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'usia'        => $request->usia,
                'pekerjaan'   => $request->pekerjaan,
                'status'      => $request->status,
                'penghasilan' => $request->penghasilan,
                'kategori'    => $kategori,
            ]
        );

        return response()->json([
            'message'  => 'Profil risiko berhasil disimpan',
            'profile'  => $profile,
            'kategori' => $kategori,
        ]);
    }

    public function show(Request $request)
    {
        $profile = RiskProfile::where(
            'user_id', $request->user()->id
        )->first();

        return response()->json($profile);
    }

    private function tentukanKategori($usia, $penghasilan)
    {
        if ($penghasilan >= 10000000) return 'agresif';
        if ($penghasilan >= 5000000)  return 'moderat';
        return 'konservatif';
    }
}