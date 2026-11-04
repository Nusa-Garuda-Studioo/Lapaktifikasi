<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Toko;
use App\Models\MutasiSaldo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class SaldoTokoController extends Controller
{
    public function index()
    {
        $shops = Toko::with('user')->get();
        return view('admin.saldo_toko.index', compact('shops'));
    }

    public function detail($id_toko)
    {
        $toko = Toko::findOrFail($id_toko);
        $mutasi = MutasiSaldo::with('dibuatOleh')
            ->where('id_toko', $id_toko)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.saldo_toko.detail', compact('toko', 'mutasi'));
    }

    public function withdraw(Request $request, $id_toko)
    {
        $toko = Toko::findOrFail($id_toko);

        $rules = [
            'nominal' => 'required|integer|min:1',
            'keterangan' => 'required|string|max:255',
        ];

        $messages = [
            'nominal.required' => 'Nominal penarikan harus diisi.',
            'nominal.integer' => 'Nominal harus berupa angka bulat.',
            'nominal.min' => 'Nominal penarikan minimal Rp 1.',
            'keterangan.required' => 'Keterangan harus diisi.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect('/saldo_toko/detail/' . $id_toko)
                ->withErrors($validator)
                ->withInput();
        }

        $nominal = (int) $request->input('nominal');

        try {
            DB::transaction(function () use ($id_toko, $nominal, $request) {
                // Lock the shop row for update to prevent race conditions
                $tokoLocked = Toko::where('id_toko', $id_toko)->lockForUpdate()->firstOrFail();

                if ($nominal > (float) $tokoLocked->saldo) {
                    throw new \Exception('Nominal penarikan melebihi saldo toko saat ini.');
                }

                $saldo_akhir = $tokoLocked->saldo - $nominal;

                MutasiSaldo::create([
                    'id_toko' => $tokoLocked->id_toko,
                    'tipe' => 'potong_withdraw',
                    'nominal' => -$nominal,
                    'saldo_akhir' => $saldo_akhir,
                    'keterangan' => $request->input('keterangan'),
                    'dibuat_oleh' => Auth::id(),
                ]);

                $tokoLocked->update([
                    'saldo' => $saldo_akhir
                ]);
            });
        } catch (\Exception $e) {
            return redirect('/saldo_toko/detail/' . $id_toko)
                ->with('error', 'Gagal withdraw: ' . $e->getMessage())
                ->withInput();
        }

        Session::flash('success', 'Withdraw manual sebesar Rp ' . number_format($nominal, 0, ',', '.') . ' berhasil diproses.');
        return redirect('/saldo_toko/detail/' . $id_toko);
    }
}
