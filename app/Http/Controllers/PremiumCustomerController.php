<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\TipeLayanan;
use App\Models\VarianLayanan;
use App\Models\StokAkun;
use App\Models\Pembelian;
use App\Models\CustomerModel;
use App\Models\CustomerTier;
use App\Models\Voucher;
use App\Models\VoucherKlaim;
use App\Models\Toko;
use App\Enums\PembelianStatus;
use App\Enums\StokStatus;
use App\Services\PaymentProcessingService;
use Barryvdh\DomPDF\Facade\Pdf;

class PremiumCustomerController extends Controller
{
    protected PaymentProcessingService $paymentProcessor;

    public function __construct(PaymentProcessingService $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
    }
    // === 1. Listing Produk -> Tipe Layanan -> Varian ===
    public function katalog(Request $request)
    {
        $search = $request->input('search');
        $toko_slug = $request->input('toko'); // Now using 'toko' slug
        $kategori = $request->input('kategori');

        $toko = null;
        $id_toko = null;
        if ($toko_slug) {
            $toko = Toko::with('badges')->where('slug', $toko_slug)->first();
            if (!$toko) {
                // Fallback to ID for backward compatibility
                $toko = Toko::with('badges')->find($toko_slug);
            }
            if ($toko && $toko->is_banned) {
                return view('premium_customer.banned_store', ['banned_reason' => $toko->banned_reason, 'nama_toko' => $toko->nama_toko]);
            }
            if ($toko) {
                $id_toko = $toko->id_toko;
            }
        }

        $query = Produk::whereIn('status', ['aktif', 'active']);

        if ($kategori && in_array($kategori, ['premium', 'digital'])) {
            $query->where('tipe_produk', $kategori);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_produk', 'like', '%' . $search . '%')
                    ->orWhere('deskripsi', 'like', '%' . $search . '%');
            });
        }

        if ($id_toko) {
            $query->where('id_toko', $id_toko);
        }

        $produk = $query->with([
            'toko',
            'tipeLayanan' => function ($query) {
                $query->whereIn('status', ['aktif', 'active'])
                    ->with([
                        'varianLayanan' => function ($vQuery) {
                            $vQuery->whereIn('status', ['aktif', 'active']);
                        }
                    ]);
            }
        ])
            ->get();

        // Calculate available stock for each variation
        foreach ($produk as $prod) {
            foreach ($prod->tipeLayanan as $tipe) {
                foreach ($tipe->varianLayanan as $varian) {
                    if ($prod->tipe_produk === 'digital') {
                        $varian->stok_tersedia = 999;
                    } else {
                        $varian->stok_tersedia = StokAkun::where('id_varian', $varian->id_varian)
                            ->where('status', StokStatus::TERSEDIA)
                            ->count();
                    }
                }
            }
        }

        $idCustomerUser = session('id');
        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();

        $reviews = null;
        $ratingDistribution = [];
        if ($id_toko && $toko) {
            $avgRating = \App\Models\Review::where('id_toko', $id_toko)->avg('rating') ?? 0;
            $countReviews = \App\Models\Review::where('id_toko', $id_toko)->count();

            // Sync rating and review count on Toko model if out of sync
            if ((float)$toko->rating_rata_rata !== (float)round($avgRating, 2) || (int)$toko->jumlah_review !== (int)$countReviews) {
                $toko->update([
                    'rating_rata_rata' => round($avgRating, 2),
                    'jumlah_review' => $countReviews,
                ]);
                $toko->refresh();
            }

            $reviews = \App\Models\Review::with('customer.user')
                ->where('id_toko', $id_toko)
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withQueryString();

            $distributionRaw = \App\Models\Review::where('id_toko', $id_toko)
                ->selectRaw('rating, count(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            for ($i = 1; $i <= 5; $i++) {
                $ratingDistribution[$i] = (int)($distributionRaw[$i] ?? 0);
            }
        }

        return view('premium_customer.katalog', compact('produk', 'customer', 'toko', 'reviews', 'ratingDistribution'));
    }

    /**
     * Tampilkan detail produk secara langsung dari rute /produk/{product_slug} (SEO Friendly)
     */
    public function showWithoutStore(string $product_slug)
    {
        return $this->show('all', $product_slug);
    }

    public function show(string $store_slug, string $product_slug)
    {
        $productId = null;
        if (is_numeric($product_slug)) {
            $productId = (int) $product_slug;
        } elseif (preg_match('/-(\d+)$/', $product_slug, $matches)) {
            $productId = (int) $matches[1];
        }

        $baseQuery = function () {
            return Produk::with([
                'toko.badges',
                'tipeLayanan' => function ($q) {
                    $q->whereIn(\DB::raw('LOWER(status)'), ['aktif', 'active'])
                      ->with([
                          'varianLayanan' => function ($vq) {
                              $vq->whereIn(\DB::raw('LOWER(status)'), ['aktif', 'active']);
                          }
                      ]);
                }
            ]);
        };

        $produk = null;

        // 1. Cari by ID produk jika ada
        if ($productId) {
            $produk = $baseQuery()->where('id_produk', $productId)->first();
        }

        // 2. Cari by slug string atau nama produk di database
        if (!$produk) {
            $cleanName = str_replace('-', ' ', strtolower($product_slug));
            $produk = $baseQuery()->where(function ($q) use ($product_slug, $cleanName) {
                $q->where('id_produk', $product_slug)
                  ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(nama_produk, ' ', '-'), '(', ''), ')', ''), '/', '')) = ?", [strtolower($product_slug)])
                  ->orWhere('nama_produk', 'like', '%' . $cleanName . '%');
            })->first();
        }

        // 3. Fallback cerdas: Cocokkan dengan Str::slug() untuk produk yang memiliki tanda kurung atau simbol
        if (!$produk) {
            $allActive = $baseQuery()->whereIn(\DB::raw('LOWER(status)'), ['aktif', 'active'])->get();
            $targetSlug = \Illuminate\Support\Str::slug($product_slug);
            foreach ($allActive as $item) {
                $itemSlug = \Illuminate\Support\Str::slug($item->nama_produk);
                $itemSlugWithId = $itemSlug . '-' . $item->id_produk;
                if ($targetSlug === $itemSlug || $targetSlug === $itemSlugWithId || str_contains($targetSlug, $itemSlug) || str_contains($itemSlug, $targetSlug)) {
                    $produk = $item;
                    break;
                }
            }
        }

        // 4. Jika store_slug dan product_slug tertukar di URL pengguna
        if (!$produk && $store_slug !== 'all') {
            if (is_numeric($store_slug)) {
                $produk = $baseQuery()->where('id_produk', (int) $store_slug)->first();
            }
        }

        if (!$produk || !in_array(strtolower(trim($produk->status)), ['aktif', 'active'])) {
            abort(404, 'Produk tidak ditemukan atau sudah tidak aktif.');
        }

        return $this->renderDetailView($produk);
    }

    public function detail($id)
    {
        return $this->show('all', (string) $id);
    }

    private function renderDetailView(Produk $produk)
    {
        $toko = $produk->toko;
        if ($toko && $toko->is_banned) {
            return view('premium_customer.banned_store', ['banned_reason' => $toko->banned_reason, 'nama_toko' => $toko->nama_toko]);
        }

        // Batch query stok untuk mencegah N+1 query
        $variantIds = $produk->tipeLayanan->flatMap->varianLayanan->pluck('id_varian')->toArray();
        $stokCounts = [];
        if (!empty($variantIds) && $produk->tipe_produk !== 'digital') {
            $stokCounts = StokAkun::whereIn('id_varian', $variantIds)
                ->where('status', StokStatus::TERSEDIA)
                ->selectRaw('id_varian, count(*) as count')
                ->groupBy('id_varian')
                ->pluck('count', 'id_varian')
                ->toArray();
        }

        $prices = [];
        foreach ($produk->tipeLayanan as $tipe) {
            foreach ($tipe->varianLayanan as $varian) {
                if ($produk->tipe_produk === 'digital') {
                    $varian->stok_tersedia = 999;
                } else {
                    $varian->stok_tersedia = $stokCounts[$varian->id_varian] ?? 0;
                }
                $prices[] = (float) $varian->harga;
            }
        }

        $minPrice = count($prices) > 0 ? min($prices) : 0;
        $maxPrice = count($prices) > 0 ? max($prices) : 0;

        // Galeri Gambar (Gambar Produk + Screenshots tambahan jika ada)
        $gallery = [];
        if ($produk->gambar) {
            $gallery[] = asset('assets/img/produk_premium/' . $produk->gambar);
        }
        $screenshots = \DB::table('tbl_screenshots_produk')->where('produk_id', $produk->id_produk)->get();
        foreach ($screenshots as $ss) {
            if (isset($ss->folder) && isset($ss->gambar)) {
                $gallery[] = asset('assets/' . $ss->folder . '/' . $ss->gambar);
            }
        }

        $idCustomerUser = session('id');
        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();

        $reviews = null;
        $ratingDistribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        if ($toko) {
            $reviews = \App\Models\Review::with('customer.user')
                ->where('id_toko', $toko->id_toko)
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withQueryString();

            $distributionRaw = \App\Models\Review::where('id_toko', $toko->id_toko)
                ->selectRaw('rating, count(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            for ($i = 1; $i <= 5; $i++) {
                $ratingDistribution[$i] = (int)($distributionRaw[$i] ?? 0);
            }
        }

        return view('premium_customer.detail', compact(
            'produk', 
            'customer', 
            'toko', 
            'reviews', 
            'ratingDistribution',
            'minPrice',
            'maxPrice',
            'gallery'
        ));
    }

    // === 2. Riwayat Pembelian Customer ===
    public function riwayat(Request $request)
    {
        $idCustomerUser = session('id');
        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();

        if (!$customer) {
            return redirect('/')->with('error', 'Profil pelanggan tidak ditemukan.');
        }

        // Sinkronisasi dan auto-expire transaksi pending dalam 24 jam terakhir
        $pendingPurchases = Pembelian::where('id_customer', $customer->id)
            ->where('status', PembelianStatus::PENDING)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        foreach ($pendingPurchases as $pPending) {
            $isExpiredByTime = $pPending->reserved_until && $pPending->reserved_until < now();
            try {
                $gatewayName = $pPending->payment_gateway ?? 'midtrans';
                $gateway = \App\Services\Gateways\PaymentGatewayFactory::make($gatewayName);
                $statusData = $gateway->verifyStatus($pPending->order_id, (int)$pPending->harga_saat_beli);

                if ($statusData['status'] === PembelianStatus::SUCCESS) {
                    $this->paymentProcessor->markAsSuccess($pPending, [
                        'payment_type' => $statusData['payment_type'] ?? 'unknown',
                        'payment_gateway' => $gatewayName,
                        'gross_amount' => $statusData['gross_amount'] ?? $pPending->harga_saat_beli,
                        'transaction_id' => $statusData['transaction_id'] ?? null,
                    ]);
                } elseif (in_array($statusData['status'], [PembelianStatus::FAILED, PembelianStatus::EXPIRED]) || $isExpiredByTime) {
                    $this->paymentProcessor->markAsFailed($pPending, $statusData['status']->value ?? 'expire', $gatewayName);
                }
            } catch (\Exception $e) {
                if ($isExpiredByTime) {
                    $this->paymentProcessor->markAsFailed($pPending, 'expire', $pPending->payment_gateway ?? 'system');
                }
            }
        }

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        
        $query = Pembelian::with(['varianLayanan.tipeLayanan.produk', 'pembayaran', 'review'])
            ->where('id_customer', $customer->id);

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
                
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Format tanggal tidak valid.');
            }
        }

        $pembelian = $query->orderBy('created_at', 'desc')->get();

        return view('premium_customer.riwayat', compact('pembelian'));
    }

    // === 3. Tampilkan Kredensial (Decrypt On-Demand) ===
    public function kredensial($order_id)
    {
        $pembelian = Pembelian::with('stokAkun')
            ->where('order_id', $order_id)
            ->firstOrFail();

        $this->authorize('view', $pembelian);

        // JANGAN PERNAH expose password KECUALI jika status sudah 'success' dan miliknya sendiri.
        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return response()->json(['error' => 'Pembayaran belum diselesaikan atau transaksi expired.'], 403);
        }

        if (!$pembelian->stokAkun) {
            return response()->json(['error' => 'Kredensial akun tidak ditemukan. Harap hubungi admin.'], 404);
        }

        // Laravel automatically decrypts password_encrypted due to encrypted cast on StokAkun model
        return response()->json([
            'email_username' => $pembelian->stokAkun->email_username,
            'password' => $pembelian->stokAkun->password_encrypted,
            'catatan' => $pembelian->stokAkun->catatan,
        ]);
    }

    // === 3b. Download File Digital ===
    public function downloadDigital($order_id)
    {
        $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk', 'customer'])
            ->where('order_id', $order_id)
            ->firstOrFail();

        $this->authorize('view', $pembelian);

        if ($pembelian->status !== \App\Enums\PembelianStatus::SUCCESS) {
            return redirect()->back()->with('error', 'Pembayaran belum diselesaikan atau transaksi expired.');
        }

        if ($pembelian->varianLayanan->tipeLayanan->produk->tipe_produk !== 'digital') {
            return redirect()->back()->with('error', 'Ini bukan produk digital.');
        }

        if (empty($pembelian->varianLayanan->file_path)) {
            return redirect()->back()->with('error', 'File belum tersedia. Silakan hubungi seller.');
        }

        $privatePath = storage_path('app/private/file_digital/' . $pembelian->varianLayanan->file_path);
        $publicPath = public_path('assets/file_digital/' . $pembelian->varianLayanan->file_path);

        if (file_exists($privatePath)) {
            $filePath = $privatePath;
        } elseif (file_exists($publicPath)) {
            $filePath = $publicPath;
        } else {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        $friendlyName = \Illuminate\Support\Str::slug($pembelian->varianLayanan->nama_varian) . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        return response()->download($filePath, $friendlyName);
    }

    // === 4. Download Invoice PDF ===
    public function downloadInvoice($order_id)
    {
        $pembelian = Pembelian::with([
            'customer.user',
            'varianLayanan.tipeLayanan.produk.toko',
            'pembayaran'
        ])
        ->where('order_id', $order_id)
        ->firstOrFail();

        $this->authorize('view', $pembelian);

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return redirect()->back()->with('error', 'Invoice hanya tersedia untuk transaksi yang sudah sukses.');
        }

        $pdf = Pdf::loadView('invoice.pdf_invoice', compact('pembelian'));
        
        return $pdf->download("invoice-{$order_id}.pdf");
    }

    // === 5. Form Review Toko ===
    public function reviewShow($order_id)
    {
        $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko', 'review'])
            ->where('order_id', $order_id)
            ->firstOrFail();

        $this->authorize('view', $pembelian);

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return redirect()->route('premium.riwayat')->with('error', 'Review hanya dapat diberikan untuk transaksi yang sukses.');
        }

        if ($pembelian->review) {
            return redirect()->route('premium.riwayat')->with('error', 'Kamu sudah memberikan review untuk transaksi ini.');
        }

        return view('premium_customer.review_form', compact('pembelian'));
    }

    // === 6. Simpan Review Toko ===
    public function reviewStore(Request $request, $order_id)
    {
        $pembelian = Pembelian::with(['varianLayanan.tipeLayanan.produk.toko', 'review', 'customer'])
            ->where('order_id', $order_id)
            ->firstOrFail();

        $this->authorize('view', $pembelian);

        if ($pembelian->status !== PembelianStatus::SUCCESS) {
            return redirect()->route('premium.riwayat')->with('error', 'Review hanya dapat diberikan untuk transaksi yang sukses.');
        }

        if ($pembelian->review) {
            return redirect()->route('premium.riwayat')->with('error', 'Kamu sudah memberikan review untuk transaksi ini.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Rating wajib diisi.',
            'rating.integer' => 'Rating harus berupa angka.',
            'rating.min' => 'Rating minimal 1.',
            'rating.max' => 'Rating maksimal 5.',
            'komentar.max' => 'Komentar maksimal 1000 karakter.',
        ]);

        // Sanitize komentar
        $komentar = $request->input('komentar') ? strip_tags($request->input('komentar')) : null;

        $toko = $pembelian->varianLayanan->tipeLayanan->produk->toko;

        // Save Review
        \App\Models\Review::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_toko' => $toko->id_toko,
            'id_customer' => $pembelian->id_customer,
            'rating' => $request->input('rating'),
            'komentar' => $komentar,
        ]);

        // Recalculate average rating & reviews count
        $toko->syncRatings();

        return redirect()->route('premium.riwayat')->with('success', 'Terima kasih! Review kamu berhasil disimpan.');
    }

    // === 5. Member / Level Saya ===
    public function member(Request $request)
    {
        $idCustomerUser = session('id');
        $customer = CustomerModel::with('tier')->where('user_id', $idCustomerUser)->first();

        if (!$customer) {
            return redirect('/')->with('error', 'Profil pelanggan tidak ditemukan.');
        }

        // 1. Progress ke tier berikutnya
        $progressInfo = $customer->progressKeTierBerikutnya();

        // 2. Daftar semua tier
        $allTiers = CustomerTier::orderBy('urutan', 'asc')->get();

        // 3. Voucher yang eligible (aktif, kuota belum habis, belum expired)
        $vouchers = Voucher::with('toko')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('kuota_total')->orWhereColumn('kuota_terpakai', '<', 'kuota_total');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Customer's claimed voucher IDs
        $claimedVoucherIds = VoucherKlaim::where('id_customer', $customer->id)
            ->pluck('id_voucher')
            ->toArray();

        return view('premium_customer.member', compact(
            'customer',
            'progressInfo',
            'allTiers',
            'vouchers',
            'claimedVoucherIds'
        ));
    }

    // === 6. Klaim Voucher Customer ===
    public function klaimVoucher(Request $request, $id_voucher)
    {
        $idCustomerUser = \Illuminate\Support\Facades\Auth::id();
        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();

        if (!$customer) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Profil pelanggan tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Profil pelanggan tidak ditemukan.');
        }

        $voucher = Voucher::find($id_voucher);

        if (!$voucher || !$voucher->is_active) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Voucher tidak aktif atau tidak ditemukan.'], 400);
            }
            return redirect()->back()->with('error', 'Voucher tidak aktif atau tidak ditemukan.');
        }

        $now = now();
        if ($voucher->berlaku_dari && $now->lt($voucher->berlaku_dari)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Voucher belum berlaku.'], 400);
            }
            return redirect()->back()->with('error', 'Voucher belum berlaku.');
        }

        if ($voucher->berlaku_sampai && $now->gt($voucher->berlaku_sampai)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Voucher telah kedaluwarsa.'], 400);
            }
            return redirect()->back()->with('error', 'Voucher telah kedaluwarsa.');
        }

        if ($voucher->kuota_total !== null && $voucher->kuota_terpakai >= $voucher->kuota_total) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Kuota voucher telah habis.'], 400);
            }
            return redirect()->back()->with('error', 'Kuota voucher telah habis.');
        }

        $alreadyClaimed = VoucherKlaim::where('id_voucher', $id_voucher)
            ->where('id_customer', $customer->id)
            ->exists();

        if ($alreadyClaimed) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Anda sudah mengklaim voucher ini.'], 400);
            }
            return redirect()->back()->with('error', 'Anda sudah mengklaim voucher ini.');
        }

        VoucherKlaim::create([
            'id_voucher' => $id_voucher,
            'id_customer' => $customer->id,
            'id_pembelian' => null,
            'created_at' => now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => 'Voucher berhasil diklaim!']);
        }

        return redirect()->back()->with('success', 'Voucher berhasil diklaim!');
    }

    // === 7. Referral Dashboard ===
    public function referral(Request $request)
    {
        $idCustomerUser = \Illuminate\Support\Facades\Auth::id();
        $customer = CustomerModel::with('tier')->where('user_id', $idCustomerUser)->first();

        if (!$customer) {
            return redirect('/')->with('error', 'Profil pelanggan tidak ditemukan.');
        }

        if (empty($customer->kode_referral)) {
            $customer->kode_referral = 'REF-' . strtoupper(\Illuminate\Support\Str::random(6));
            $customer->save();
        }

        $shareUrl = url('/pendaftaran?ref=' . $customer->kode_referral);
        $bonusAmount = (float) config('referral.bonus_akumulasi', 50000);

        $referredCustomers = CustomerModel::with('user')
            ->where('direferensikan_oleh', $customer->id)
            ->get();

        return view('premium_customer.referral', compact('customer', 'shareUrl', 'bonusAmount', 'referredCustomers'));
    }
}
