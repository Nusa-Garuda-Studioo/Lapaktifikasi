<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeliProdukModel;
use App\Models\User;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\PembayaranModel;
use App\Models\Toko;
use App\Models\MutasiSaldo;
use App\Models\ProdukTerjualModel;
use App\Enums\PembelianStatus;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role_id == \App\Enums\Role::SELLER->value) {
            return redirect()->route('seller.dashboard');
        }

        if (Auth::user()->role_id == \App\Enums\Role::CUSTOMER->value) {
            return redirect()->route('premium.katalog');
        }

        $hari_ini = date('Y-m-d');
        $total_order_hari_ini = $this->getTotalOrderHariIni($hari_ini);
        $total_penjualan_hari_ini = $this->getTotalPenjualanHariIni($hari_ini);
        $total_barang_terjual_hari_ini = $this->getTotalBarangTerjualHariIni($hari_ini);
        $nama_order_hari_ini = $this->getNamaOrderHariIni($hari_ini);

        return view('dashboard.index', compact(
            'total_order_hari_ini',
            'total_penjualan_hari_ini',
            'total_barang_terjual_hari_ini',
            'nama_order_hari_ini'
        ));
    }

    protected function getTotalOrderHariIni($hari_ini)
    {
        // Legacy orders today
        $legacyCount = BeliProdukModel::where('tanggal_transaksi', $hari_ini)->count();
        
        // Premium orders today
        $premiumCount = Pembelian::whereDate('created_at', $hari_ini)->count();

        return $legacyCount + $premiumCount;
    }

    protected function getTotalPenjualanHariIni($hari_ini)
    {
        // Legacy sales today
        $legacySales = PembayaranModel::whereIn('order_id', BeliProdukModel::where('tanggal_transaksi', $hari_ini)->pluck('order_id'))->sum('total');

        // Premium sales today
        $premiumSales = Pembayaran::whereDate('tanggal_bayar', $hari_ini)->sum('jumlah_dibayar');

        $total = $legacySales + $premiumSales;

        return number_format($total, 0, ',', '.');
    }

    protected function getTotalBarangTerjualHariIni($hari_ini)
    {
        // Legacy items sold today
        $legacyQty = BeliProdukModel::where('status', 'success')
            ->where('tanggal_transaksi', $hari_ini)
            ->sum('qty');

        // Premium items sold today (count of successful purchases paid today)
        $premiumQty = Pembelian::where('status', PembelianStatus::SUCCESS)
            ->whereHas('pembayaran', function ($query) use ($hari_ini) {
                $query->whereDate('tanggal_bayar', $hari_ini);
            })
            ->count();

        return $legacyQty + $premiumQty;
    }

    protected function getNamaOrderHariIni($hari_ini)
    {
        $orders = [];

        // Legacy orders today
        $legacyOrders = BeliProdukModel::with('users')
            ->where('tanggal_transaksi', $hari_ini)
            ->get();

        foreach ($legacyOrders as $order) {
            $orders[] = [
                'nama_customer' => $order->users->name ?? $order->users->email ?? '-',
                'order_id' => $order->order_id,
                'status' => ucfirst($order->status),
            ];
        }

        // Premium orders today
        $premiumOrders = Pembelian::with('customer.user')
            ->whereDate('created_at', $hari_ini)
            ->get();

        foreach ($premiumOrders as $order) {
            $orders[] = [
                'nama_customer' => $order->customer->user->name ?? $order->customer->user->email ?? '-',
                'order_id' => $order->order_id,
                'status' => ucfirst($order->status->value),
            ];
        }

        return $orders;
    }

    public function seller_index(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $toko = Toko::where('user_id', $user_id)->first();
        if (!$toko) {
            $namaToko = $user->name ? $user->name . ' Store' : 'Toko Seller #' . $user_id;
            $toko = Toko::create([
                'user_id' => $user_id,
                'nama_toko' => $namaToko,
                'slug' => Toko::generateUniqueSlug($namaToko),
                'no_telp' => '-',
                'akun_telegram' => '@' . ($user->telegram_username ?: 'seller' . $user_id),
                'informasi_toko' => 'Toko resmi ' . ($user->name ?: 'Seller'),
                'saldo' => 0,
                'status' => 'aktif',
            ]);
        }
        $toko_id = $toko->id_toko;

        // Default range is today
        $range = $request->input('filter_range', 'today');
        $startDate = now()->startOfDay();
        $endDate = now()->endOfDay();

        if ($range === '7_days') {
            $startDate = now()->subDays(6)->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($range === 'this_month') {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfDay();
        } elseif ($range === 'custom') {
            $startDateInput = $request->input('start_date');
            $endDateInput = $request->input('end_date');
            if ($startDateInput && $endDateInput) {
                try {
                    $startDate = \Illuminate\Support\Carbon::parse($startDateInput)->startOfDay();
                    $endDate = \Illuminate\Support\Carbon::parse($endDateInput)->endOfDay();
                    
                    if ($startDate->greaterThan($endDate)) {
                        return redirect()->back()->with('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
                    }
                    
                    if ($startDate->diffInDays($endDate) > 365) {
                        return redirect()->back()->with('error', 'Rentang tanggal maksimal adalah 1 tahun.');
                    }
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', 'Format tanggal tidak valid.');
                }
            }
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();

        // 1. Jumlah order sukses dalam rentang tanggal (khusus toko ini)
        $legacy_order = BeliProdukModel::where('id_toko', $toko_id)
            ->where('status', 'success')
            ->whereBetween('tanggal_transaksi', [$startDateStr, $endDateStr])
            ->count();

        $premium_order = Pembelian::where('status', PembelianStatus::SUCCESS)
            ->whereHas('varianLayanan.tipeLayanan.produk', function ($query) use ($toko_id) {
                $query->where('id_toko', $toko_id);
            })
            ->whereHas('pembayaran', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_bayar', [$startDate, $endDate]);
            })
            ->count();

        $total_order = $legacy_order + $premium_order;

        // 2. Total omzet dalam rentang tanggal (khusus toko ini)
        $legacy_omzet = PembayaranModel::whereIn('order_id', 
            BeliProdukModel::where('id_toko', $toko_id)
                ->where('status', 'success')
                ->whereBetween('tanggal_transaksi', [$startDateStr, $endDateStr])
                ->pluck('order_id')
        )->sum('total');

        $premium_omzet = Pembayaran::whereHas('pembelian.varianLayanan.tipeLayanan.produk', function ($query) use ($toko_id) {
                $query->where('id_toko', $toko_id);
            })
            ->whereHas('pembelian', function ($query) {
                $query->where('status', PembelianStatus::SUCCESS);
            })
            ->whereBetween('tanggal_bayar', [$startDate, $endDate])
            ->sum('jumlah_dibayar');

        $total_omzet_raw = $legacy_omzet + $premium_omzet;
        $total_penjualan = number_format($total_omzet_raw, 0, ',', '.');

        // 3. Saldo berjalan toko ini saat ini
        $saldo_toko = $toko->saldo;

        // 4. Total produk terjual (akumulasi, khusus toko ini)
        $legacy_terjual = ProdukTerjualModel::whereHas('produk', function($query) use ($toko_id) {
            $query->where('id_toko', $toko_id);
        })->sum('jumlah_terjual');

        $premium_terjual = \App\Models\StokAkun::where('status', \App\Enums\StokStatus::TERJUAL)
            ->whereHas('varianLayanan.tipeLayanan.produk', function ($query) use ($toko_id) {
                $query->where('id_toko', $toko_id);
            })
            ->count();

        $total_produk_terjual = $legacy_terjual + $premium_terjual;

        // 5. Riwayat mutasi saldo toko ini (filter berdasarkan range created_at)
        $riwayat_mutasi = MutasiSaldo::where('id_toko', $toko_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('id', 'desc')
            ->get();

        // 6. Tiket laporan kendala garansi pelanggan terkait toko ini
        $tiket_kendala = \App\Models\Laporan::with(['user.customer', 'pesan'])
            ->where(function ($q) use ($user_id, $toko_id) {
                $q->where('assigned_to', $user_id)
                  ->orWhereHas('user.customer.pembelian.varianLayanan.tipeLayanan.produk', function ($qp) use ($toko_id) {
                      $qp->where('id_toko', $toko_id);
                  });
            })
            ->latest()
            ->take(6)
            ->get();

        return view('seller.dashboard', compact(
            'toko',
            'total_order',
            'total_penjualan',
            'saldo_toko',
            'total_produk_terjual',
            'riwayat_mutasi',
            'tiket_kendala',
            'range',
            'startDate',
            'endDate'
        ));
    }

    public function seller_mutasi()
    {
        $user = Auth::user();
        $user_id = $user->id;
        $toko = Toko::where('user_id', $user_id)->first();
        if (!$toko) {
            $namaToko = $user->name ? $user->name . ' Store' : 'Toko Seller #' . $user_id;
            $toko = Toko::create([
                'user_id' => $user_id,
                'nama_toko' => $namaToko,
                'slug' => Toko::generateUniqueSlug($namaToko),
                'no_telp' => '-',
                'akun_telegram' => '@' . ($user->telegram_username ?: 'seller' . $user_id),
                'informasi_toko' => 'Toko resmi ' . ($user->name ?: 'Seller'),
                'saldo' => 0,
                'status' => 'aktif',
            ]);
        }
        $toko_id = $toko->id_toko;

        $mutasi = MutasiSaldo::where('id_toko', $toko_id)
            ->orderBy('id', 'desc')
            ->get();

        return view('seller.mutasi', compact('toko', 'mutasi'));
    }
}
