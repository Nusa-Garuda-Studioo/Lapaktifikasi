<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerVoucherController extends Controller
{
    private function getSellerToko()
    {
        $user = Auth::user();
        $toko = Toko::where('user_id', $user->id)->first();
        if (!$toko) {
            $namaToko = $user->name ? $user->name . ' Store' : 'Toko Seller #' . $user->id;
            $toko = Toko::create([
                'user_id' => $user->id,
                'nama_toko' => $namaToko,
                'slug' => Toko::generateUniqueSlug($namaToko),
                'no_telp' => '-',
                'akun_telegram' => '@' . ($user->telegram_username ?: 'seller' . $user->id),
                'informasi_toko' => 'Toko resmi ' . ($user->name ?: 'Seller'),
                'saldo' => 0,
                'status' => 'aktif',
            ]);
        }
        return $toko;
    }

    public function index(Request $request)
    {
        $toko = $this->getSellerToko();
        $search = $request->input('search');

        $query = Voucher::where('id_toko', $toko->id_toko)
            ->where('scope', 'toko_spesifik')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where('kode', 'like', '%' . strtoupper($search) . '%');
        }

        $vouchers = $query->paginate(15)->withQueryString();

        return view('seller.voucher.index', compact('vouchers', 'toko'));
    }

    public function create()
    {
        $toko = $this->getSellerToko();
        return view('seller.voucher.create', compact('toko'));
    }

    public function store(Request $request)
    {
        $toko = $this->getSellerToko();

        $request->validate([
            'kode' => 'required|string|max:50|unique:tbl_voucher,kode',
            'tipe_diskon' => 'required|in:persen,nominal',
            'nilai_diskon' => 'required|numeric|min:0',
            'maksimal_potongan' => 'nullable|numeric|min:0',
            'minimal_transaksi' => 'required|numeric|min:0',
            'kuota_total' => 'nullable|integer|min:1',
            'berlaku_dari' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
        ]);

        Voucher::create([
            'kode' => strtoupper(trim($request->kode)),
            'tipe_diskon' => $request->tipe_diskon,
            'nilai_diskon' => $request->nilai_diskon,
            'maksimal_potongan' => $request->tipe_diskon === 'persen' ? $request->maksimal_potongan : null,
            'minimal_transaksi' => $request->minimal_transaksi ?? 0,
            'kuota_total' => $request->kuota_total,
            'kuota_terpakai' => 0,
            'berlaku_dari' => $request->berlaku_dari,
            'berlaku_sampai' => $request->berlaku_sampai,
            'scope' => 'toko_spesifik',
            'id_toko' => $toko->id_toko,
            'dibuat_oleh' => Auth::id(),
            'is_active' => true,
        ]);

        return redirect()->route('seller.voucher.index')->with('success', 'Voucher Toko berhasil dibuat!');
    }

    public function edit($id)
    {
        $toko = $this->getSellerToko();
        $voucher = Voucher::where('id_voucher', $id)->where('id_toko', $toko->id_toko)->firstOrFail();

        return view('seller.voucher.edit', compact('voucher', 'toko'));
    }

    public function update(Request $request, $id)
    {
        $toko = $this->getSellerToko();
        $voucher = Voucher::where('id_voucher', $id)->where('id_toko', $toko->id_toko)->firstOrFail();

        $request->validate([
            'kode' => 'required|string|max:50|unique:tbl_voucher,kode,' . $id . ',id_voucher',
            'tipe_diskon' => 'required|in:persen,nominal',
            'nilai_diskon' => 'required|numeric|min:0',
            'maksimal_potongan' => 'nullable|numeric|min:0',
            'minimal_transaksi' => 'required|numeric|min:0',
            'kuota_total' => 'nullable|integer|min:1',
            'berlaku_dari' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_dari',
        ]);

        $voucher->update([
            'kode' => strtoupper(trim($request->kode)),
            'tipe_diskon' => $request->tipe_diskon,
            'nilai_diskon' => $request->nilai_diskon,
            'maksimal_potongan' => $request->tipe_diskon === 'persen' ? $request->maksimal_potongan : null,
            'minimal_transaksi' => $request->minimal_transaksi ?? 0,
            'kuota_total' => $request->kuota_total,
            'berlaku_dari' => $request->berlaku_dari,
            'berlaku_sampai' => $request->berlaku_sampai,
        ]);

        return redirect()->route('seller.voucher.index')->with('success', 'Voucher Toko berhasil diperbarui!');
    }

    public function toggleStatus($id)
    {
        $toko = $this->getSellerToko();
        $voucher = Voucher::where('id_voucher', $id)->where('id_toko', $toko->id_toko)->firstOrFail();

        $voucher->update(['is_active' => !$voucher->is_active]);

        $statusText = $voucher->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Voucher {$voucher->kode} berhasil {$statusText}.");
    }
}
