<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel;
use Illuminate\Http\Request;
use App\Models\ProdukModel;
use App\Models\TipeLayanan;
use App\Models\VarianLayanan;
use App\Models\StokAkun;
use App\Models\User;
use App\Models\BeliProdukModel;
use App\Models\ScreenshotsProdukModel;
use App\Models\Toko;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

class ProductController extends Controller
{
    protected $messages = [
        'nama_produk.required' => 'Nama tidak boleh kosong.',
        'deskripsi.required' => 'Deskripsi tidak boleh kosong.',
        'status.required' => 'Status tidak boleh kosong.',
        'harga.required' => 'Harga tidak boleh kosong.',
        'harga.numeric' => 'Inputan harga harus berupa angka.',
        'harga.max_digits' => 'Nominal harga tidak boleh lebih dari 10 digit.',
        'file.required' => 'File zip harus di upload.',
        'file.extensions' => 'File yang Anda upload tidak valid.',
        'file.mimetypes' => 'File yang Anda upload tidak valid.',
        'gambar.image' => 'Cover image harus berupa gambar.',
        'gambar.mimes' => 'Format cover image harus jpeg, png, jpg, atau webp.',
        'gambar.max' => 'Ukuran cover image maksimal 2MB.',
    ];

    protected function setSessionFlash($detectMessage, $message)
    {
        Session::flash($detectMessage, $message);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user_id = Auth::id();
        $role = User::find($user_id);

        if (!$role) {
            return redirect('/login');
        }

        if ($role->role_id == 1) {
            // Admin sees all products (read-only)
            $semuaProduk = ProdukModel::with('toko')->get();
        } else if ($role->role_id == 3) {
            // Seller sees only their own products
            $toko = Toko::where('user_id', $user_id)->first();
            $semuaProduk = $toko ? ProdukModel::where('id_toko', $toko->id_toko)->get() : collect();
        } else if ($role->role_id == 2) {
            // Customer: redirect to daftar toko
            return redirect('/daftar_toko');
        }

        return view('produk.index', ($role->role_id == 1 || $role->role_id == 3 ? compact('semuaProduk') : []));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (Auth::user()->role_id != 3) {
            abort(403, 'Forbidden access. Only sellers can manage products.');
        }
        return view('produk.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role_id != 3) {
            abort(403, 'Forbidden access. Only sellers can manage products.');
        }

        $toko = Toko::where('user_id', Auth::id())->first();
        if (!$toko) {
            abort(403, 'Toko Anda tidak ditemukan.');
        }

        $rules = [
            'nama_produk' => 'required|max:100',
            'deskripsi'   => 'nullable',
            'status'      => 'required|in:aktif,nonaktif',
            'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);
        if ($validator->fails()) {
            return redirect('/menu_produk/create')->withErrors($validator)->withInput();
        }

        $gambarName = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $gambarName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/img/produk_premium'), $gambarName);
        }

        ProdukModel::create([
            'nama_produk' => $request->input('nama_produk'),
            'tipe_produk' => 'premium',
            'deskripsi'   => $request->input('deskripsi'),
            'gambar'      => $gambarName,
            'file'        => null,
            'harga'       => null,
            'status'      => $request->input('status'),
            'id_toko'     => $toko->id_toko,
        ]);

        $this->setSessionFlash('success', 'Produk berhasil ditambahkan.');
        return redirect('/menu_produk');
    }

    /**
     * Redirect to SEO-friendly product detail route.
     */
    public function show(string $id)
    {
        $produk = Produk::with(['toko'])->find($id);
        if ($produk && $produk->toko) {
            $storeSlug = \Illuminate\Support\Str::slug($produk->toko->nama_toko) . '-' . $produk->toko->id_toko;
            $productSlug = \Illuminate\Support\Str::slug($produk->nama_produk) . '-' . $produk->id_produk;
            return redirect()->route('toko.produk.detail', ['store_slug' => $storeSlug, 'product_slug' => $productSlug]);
        }
        return redirect()->route('premium.katalog');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        if (Auth::user()->role_id != 3) {
            abort(403, 'Forbidden access. Only sellers can manage products.');
        }

        $toko = Toko::where('user_id', Auth::id())->first();
        $data = ProdukModel::findOrFail($id);

        if (!$toko || $data->id_toko != $toko->id_toko) {
            abort(403, 'Forbidden access. Anda bukan pemilik produk ini.');
        }

        return view('produk.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (Auth::user()->role_id != 3) {
            abort(403, 'Forbidden access. Only sellers can manage products.');
        }

        $toko = Toko::where('user_id', Auth::id())->first();
        $updateProduct = ProdukModel::findOrFail($id);

        if (!$toko || $updateProduct->id_toko != $toko->id_toko) {
            abort(403, 'Anda bukan pemilik produk ini.');
        }

        $rules = [
            'nama_produk' => 'required|max:100',
            'deskripsi'   => 'nullable',
            'status'      => 'required|in:aktif,nonaktif',
            'gambar'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);
        if ($validator->fails()) {
            return redirect('/menu_produk/' . $id . '/edit')->withErrors($validator)->withInput();
        }

        $updateProduct->nama_produk = $request->input('nama_produk');
        $updateProduct->tipe_produk = 'premium';
        $updateProduct->deskripsi   = $request->input('deskripsi');
        $updateProduct->status      = $request->input('status');

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama
            if ($updateProduct->gambar && file_exists(public_path('assets/img/produk_premium/' . $updateProduct->gambar))) {
                @unlink(public_path('assets/img/produk_premium/' . $updateProduct->gambar));
            }
            $file = $request->file('gambar');
            $gambarName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/img/produk_premium'), $gambarName);
            $updateProduct->gambar = $gambarName;
        }

        $updateProduct->save();

        $this->setSessionFlash('success', 'Produk berhasil diupdate.');
        return redirect('/menu_produk');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (Auth::user()->role_id != 3 && Auth::user()->role_id != 1) {
            abort(403, 'Forbidden access. Only sellers or admins can manage products.');
        }

        $user_id = Auth::id();
        $role_id = Auth::user()->role_id;
        $produk = ProdukModel::findOrFail($id);

        if ($role_id == 3) {
            $toko = Toko::where('user_id', $user_id)->first();
            if (!$toko || $produk->id_toko != $toko->id_toko) {
                abort(403, 'Anda bukan pemilik produk ini.');
            }
        }

        try {
            $tipeIds = TipeLayanan::where('id_produk', $produk->id_produk)->pluck('id_tipe');
            $varianIds = VarianLayanan::whereIn('id_tipe', $tipeIds)->pluck('id_varian');
            $hasPurchases = \App\Models\Pembelian::whereIn('id_varian', $varianIds)->exists();

            if ($hasPurchases) {
                StokAkun::whereIn('id_varian', $varianIds)->where('status', \App\Enums\StokStatus::TERSEDIA)->delete();
                VarianLayanan::whereIn('id_tipe', $tipeIds)->update(['status' => 'nonaktif']);
                TipeLayanan::where('id_produk', $produk->id_produk)->update(['status' => 'nonaktif']);
                $produk->update(['status' => 'nonaktif']);

                $this->setSessionFlash('success', 'Produk ini memiliki riwayat transaksi. Status produk & varian diubah menjadi non-aktif agar data pembeli tetap aman.');
                return redirect('/menu_produk');
            }

            StokAkun::whereIn('id_varian', $varianIds)->delete();
            VarianLayanan::whereIn('id_tipe', $tipeIds)->delete();
            TipeLayanan::where('id_produk', $produk->id_produk)->delete();

            if ($produk->gambar && file_exists(public_path('assets/img/produk_premium/' . $produk->gambar))) {
                @unlink(public_path('assets/img/produk_premium/' . $produk->gambar));
            }

            $produk->delete();
            $this->setSessionFlash('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            TipeLayanan::where('id_produk', $produk->id_produk)->update(['status' => 'nonaktif']);
            $produk->update(['status' => 'nonaktif']);
            $this->setSessionFlash('success', 'Produk diubah menjadi non-aktif agar data transaksi tetap aman.');
        }

        return redirect('/menu_produk');
    }

    // beli() dan proses_checkout() dihapus — platform hanya pakai proses_checkout_premium()

    public function produk_terjual()
    {
        $user_id = Auth::id();
        $role = User::find($user_id);

        if ($role->role_id == 1) {
            // Admin sees all sold products
            $produk = ProdukModel::withSum('produk_terjual', 'jumlah_terjual')
                ->get()
                ->filter(function ($item) {
                    return $item->produk_terjual_sum_jumlah_terjual > 0;
                });
        } else if ($role->role_id == 3) {
            // Seller sees only their own sold products
            $toko = Toko::where('user_id', $user_id)->first();
            $toko_id = $toko ? $toko->id_toko : 0;
            $produk = ProdukModel::where('id_toko', $toko_id)
                ->withSum('produk_terjual', 'jumlah_terjual')
                ->get()
                ->filter(function ($item) {
                    return $item->produk_terjual_sum_jumlah_terjual > 0;
                });
        } else {
            abort(403);
        }

        return view('produk.produk_terjual', compact('produk'));
    }

    // download_produk() dihapus — tidak ada lagi produk biasa/ZIP

    public function proses_checkout_premium(Request $request)
    {
        $id_varian = $request->input('id_varian');
        $kode_voucher = strtoupper(trim((string) $request->input('kode_voucher')));
        $idCustomerUser = Auth::id();

        $customer = CustomerModel::where('user_id', $idCustomerUser)->first();
        if (!$customer) {
            return redirect()->back()->with('error', 'Customer profile not found.');
        }

        if (empty($customer->nomor_telepon) || empty($customer->user->name)) {
            return redirect('profile_customer/' . $idCustomerUser)->with('error', 'Silakan lengkapi nama dan nomor telepon WhatsApp Anda di profil terlebih dahulu sebelum melakukan pembelian.');
        }

        $id_customer = $customer->id;

        try {
            $pembelian = \Illuminate\Support\Facades\DB::transaction(function () use ($id_varian, $id_customer, $kode_voucher) {
                $varian = \App\Models\VarianLayanan::with('tipeLayanan.produk')->findOrFail($id_varian);
                $isDigital = $varian->tipeLayanan?->produk?->tipe_produk === 'digital';

                $stok = null;
                if (!$isDigital) {
                    $stok = \App\Models\StokAkun::where('id_varian', $id_varian)
                        ->where('status', \App\Enums\StokStatus::TERSEDIA)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->first();

                    if (!$stok) {
                        throw new \App\Exceptions\StokHabisException('Stok Habis');
                    }
                }

                $harga_varian = (float) $varian->harga;
                $harga_saat_beli = $harga_varian;
                $nominal_diskon = 0;
                $voucher_dipakai = null;

                // Process Voucher if provided
                if (!empty($kode_voucher)) {
                    $voucher = \App\Models\Voucher::where('kode', $kode_voucher)->lockForUpdate()->first();

                    if (!$voucher || !$voucher->is_active) {
                        throw new \Exception("Kode voucher '{$kode_voucher}' tidak valid atau tidak aktif.");
                    }

                    $now = now();
                    if ($voucher->berlaku_dari && $now->lt($voucher->berlaku_dari)) {
                        throw new \Exception("Voucher '{$kode_voucher}' belum berlaku.");
                    }

                    if ($voucher->berlaku_sampai && $now->gt($voucher->berlaku_sampai)) {
                        throw new \Exception("Voucher '{$kode_voucher}' sudah kedaluwarsa.");
                    }

                    if ($voucher->kuota_total !== null && $voucher->kuota_terpakai >= $voucher->kuota_total) {
                        throw new \Exception("Kuota voucher '{$kode_voucher}' telah habis.");
                    }

                    // Scope check
                    $idTokoProduk = $varian->tipeLayanan?->produk?->id_toko;
                    if ($voucher->scope === 'toko_spesifik' && $voucher->id_toko != $idTokoProduk) {
                        throw new \Exception("Voucher '{$kode_voucher}' tidak berlaku untuk toko produk ini.");
                    }

                    // Minimal transaction check
                    if ($harga_varian < (float) $voucher->minimal_transaksi) {
                        $minRp = number_format((float) $voucher->minimal_transaksi, 0, ',', '.');
                        throw new \Exception("Minimal transaksi untuk voucher ini adalah Rp {$minRp}.");
                    }

                    // Auto-claim if not claimed yet
                    $existingKlaim = \App\Models\VoucherKlaim::where('id_voucher', $voucher->id_voucher)
                        ->where('id_customer', $id_customer)
                        ->lockForUpdate()
                        ->first();

                    if (!$existingKlaim) {
                        \App\Models\VoucherKlaim::create([
                            'id_voucher' => $voucher->id_voucher,
                            'id_customer' => $id_customer,
                            'diklaim_pada' => now(),
                        ]);
                    }

                    // Calculate discount
                    if ($voucher->tipe_diskon === 'persentase') {
                        $potongan = ($harga_varian * (float) $voucher->nilai_diskon) / 100;
                        if ($voucher->maksimal_potongan !== null) {
                            $potongan = min($potongan, (float) $voucher->maksimal_potongan);
                        }
                    } else {
                        $potongan = (float) $voucher->nilai_diskon;
                    }

                    $nominal_diskon = min($harga_varian, max(0.0, $potongan));
                    $harga_saat_beli = max(0.0, $harga_varian - $nominal_diskon);
                    $voucher_dipakai = $voucher->id_voucher;
                }

                $reserved_expired_at = now()->addMinutes(15);

                if ($stok) {
                    $stok->update([
                        'status' => \App\Enums\StokStatus::RESERVED,
                        'reserved_at' => now(),
                        'reserved_expired_at' => $reserved_expired_at,
                    ]);
                }

                $pembelian = \App\Models\Pembelian::create([
                    'order_id' => (string) \Illuminate\Support\Str::ulid(),
                    'id_customer' => $id_customer,
                    'id_varian' => $id_varian,
                    'id_stok' => $stok?->id_stok,
                    'harga_saat_beli' => $harga_saat_beli,
                    'id_voucher_dipakai' => $voucher_dipakai,
                    'nominal_diskon' => $nominal_diskon,
                    'status' => \App\Enums\PembelianStatus::PENDING,
                    'reserved_until' => $reserved_expired_at,
                ]);

                if ($stok) {
                    $stok->update([
                        'id_pembelian' => $pembelian->id_pembelian,
                    ]);
                }

                return $pembelian;
            });

            return redirect()->route('metode_pembayaran', $pembelian->order_id);

        } catch (\App\Exceptions\StokHabisException $e) {
            return redirect()->back()->with('error', 'Stok Habis');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function get_stok_varian(int $id_varian)
    {
        $varian = \App\Models\VarianLayanan::with('tipeLayanan.produk')->find($id_varian);
        if ($varian && $varian->tipeLayanan?->produk?->tipe_produk === 'digital') {
            return response()->json([
                'id_varian' => $id_varian,
                'stok' => 999
            ]);
        }

        $stok = \App\Models\StokAkun::where('id_varian', $id_varian)
            ->where('status', \App\Enums\StokStatus::TERSEDIA)
            ->count();

        return response()->json([
            'id_varian' => $id_varian,
            'stok' => $stok
        ]);
    }

    // extract_screenshots() dan proses_extract_screenshots() dihapus — tidak ada lagi produk biasa

    // === 4. Customer Browsing (Daftar Toko & Katalog Scoped) ===
    public function daftar_toko()
    {
        $shops = Toko::where('status', 'aktif')->paginate(12);
        return view('customer.daftar_toko', compact('shops'));
    }

    /**
     * Katalog toko per-toko untuk Customer.
     * Redirect ke PremiumCustomerController@katalog dengan filter id_toko.
     * Scoping: hanya produk premium milik toko tersebut yang tampil.
     */
    public function katalog_toko($store_slug)
    {
        // Validasi toko aktif (mendukung slug, slug-id, atau id numerik)
        $toko = Toko::where('slug', $store_slug)->first();
        if (!$toko && preg_match('/-(\d+)$/', $store_slug, $matches)) {
            $toko = Toko::find($matches[1]);
        }
        if (!$toko && is_numeric($store_slug)) {
            $toko = Toko::find($store_slug);
        }
        if (!$toko) {
            $toko = Toko::where('nama_toko', 'like', '%' . str_replace('-', ' ', $store_slug) . '%')->first();
        }

        if (!$toko || $toko->status !== 'aktif') {
            abort(404, 'Toko tidak ditemukan atau sedang tidak aktif.');
        }

        // Delegate ke premium catalog dengan filter toko
        return redirect()->route('premium.katalog', ['toko' => $toko->slug ?? $toko->id_toko]);
    }
}
