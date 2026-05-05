<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', $request->user()->id);

        if ($request->bulan) {
            $query->whereMonth('tanggal', $request->bulan);
        }

        if ($request->tahun) {
            $query->whereYear('tanggal', $request->tahun);
        }

        if ($request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->tipe) {
            $query->where('tipe', $request->tipe);
        }

        $transactions = $query->orderBy('tanggal', 'desc')->get();

        $totalPemasukan   = $transactions->where('tipe', 'pemasukan')->sum('jumlah');
        $totalPengeluaran = $transactions->where('tipe', 'pengeluaran')->sum('jumlah');

        return response()->json([
            'transactions'      => $transactions,
            'total_pemasukan'   => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'selisih'           => $totalPemasukan - $totalPengeluaran,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'     => 'required|string',
            'jumlah'   => 'required|numeric',
            'tipe'     => 'required|in:pemasukan,pengeluaran',
            'kategori' => 'required|string',
            'tanggal'  => 'required|date',
        ]);

        $transaction = Transaction::create([
            'user_id'  => $request->user()->id,
            'nama'     => $request->nama,
            'jumlah'   => $request->jumlah,
            'tipe'     => $request->tipe,
            'kategori' => $request->kategori,
            'tanggal'  => $request->tanggal,
        ]);

        return response()->json([
            'message'     => 'Transaksi berhasil disimpan',
            'transaction' => $transaction,
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $transaction = Transaction::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $transaction->delete();

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }
}