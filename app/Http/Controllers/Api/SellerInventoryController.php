<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\TipeLayanan;
use App\Models\VarianLayanan;
use App\Models\StokAkun;
use App\Models\SettingKomisi;
use App\Models\Pembelian;
use App\Enums\StokStatus;
use App\Enums\PembelianStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SellerInventoryController extends ApiController
{
    private function getSellerToko(Request $request): ?Toko
    {
        return Toko::where('user_id', $request->user()->id)->first();
    }

    // ==========================================
    // 1. TIPE LAYANAN (CRUD)
    // ==========================================

    public function indexTipe(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $idProduk = $request->input('id_produk');
        $query = TipeLayanan::whereHas('produk', function($q) use ($toko) {
            $q->where('id_toko', $toko->id_toko);
        })->with(['produk:id,nama_produk,tipe_produk', 'varianLayanan']);

        if ($idProduk) {
            $query->where('id_produk', $idProduk);
        }

        $perPage = (int) $request->input('per_page', 20);
        $tipe = $query->paginate($perPage);

        return $this->sendResponse($tipe, 'Daftar tipe layanan berhasil diambil');
    }

    public function storeTipe(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $validator = Validator::make($request->all(), [
            'id_produk' => 'required|exists:tbl_produk,id',
            'nama_tipe' => 'required|max:50',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $produk = Produk::where('id', $request->id_produk)->where('id_toko', $toko->id_toko)->first();
        if (!$produk) return $this->sendError('Produk tidak ditemukan atau bukan milik Anda', [], 403);

        $tipe = TipeLayanan::create([
            'id_produk' => $request->id_produk,
            'nama_tipe' => $request->nama_tipe,
            'status' => $request->status,
        ]);

        return $this->sendResponse($tipe, 'Tipe layanan berhasil ditambahkan', 201);
    }

    public function updateTipe(Request $request, $id)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $tipe = TipeLayanan::with('produk')->find($id);
        if (!$tipe || $tipe->produk?->id_toko != $toko->id_toko) {
            return $this->sendError('Tipe layanan tidak ditemukan atau bukan milik Anda', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_produk' => 'sometimes|exists:tbl_produk,id',
            'nama_tipe' => 'required|max:50',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        if ($request->has('id_produk')) {
            $prod = Produk::where('id', $request->id_produk)->where('id_toko', $toko->id_toko)->first();
            if (!$prod) return $this->sendError('Produk tujuan bukan milik Anda', [], 403);
            $tipe->id_produk = $request->id_produk;
        }

        $tipe->nama_tipe = $request->nama_tipe;
        $tipe->status = $request->status;
        $tipe->save();

        return $this->sendResponse($tipe, 'Tipe layanan berhasil diperbarui');
    }

    public function destroyTipe(Request $request, $id)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $tipe = TipeLayanan::with('produk')->find($id);
        if (!$tipe || $tipe->produk?->id_toko != $toko->id_toko) {
            return $this->sendError('Tipe layanan tidak ditemukan atau bukan milik Anda', [], 404);
        }

        try {
            $varianIds = VarianLayanan::where('id_tipe', $tipe->id_tipe)->pluck('id_varian');
            $hasPurchases = Pembelian::whereIn('id_varian', $varianIds)->exists();

            if ($hasPurchases) {
                StokAkun::whereIn('id_varian', $varianIds)->where('status', StokStatus::TERSEDIA)->delete();
                VarianLayanan::whereIn('id_tipe', $tipe->id_tipe)->update(['status' => 'nonaktif']);
                $tipe->update(['status' => 'nonaktif']);

                return $this->sendResponse([], 'Tipe memiliki riwayat transaksi, status diubah menjadi nonaktif.');
            }

            StokAkun::whereIn('id_varian', $varianIds)->delete();
            VarianLayanan::whereIn('id_tipe', $tipe->id_tipe)->delete();
            $tipe->delete();

            return $this->sendResponse([], 'Tipe layanan berhasil dihapus');
        } catch (\Exception $e) {
            $tipe->update(['status' => 'nonaktif']);
            return $this->sendResponse([], 'Tipe diubah menjadi nonaktif demi keamanan transaksi.');
        }
    }

    // ==========================================
    // 2. VARIAN LAYANAN (CRUD)
    // ==========================================

    public function indexVarian(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $idTipe = $request->input('id_tipe');
        $query = VarianLayanan::whereHas('tipeLayanan.produk', function($q) use ($toko) {
            $q->where('id_toko', $toko->id_toko);
        })->with(['tipeLayanan.produk']);

        if ($idTipe) {
            $query->where('id_tipe', $idTipe);
        }

        $perPage = (int) $request->input('per_page', 20);
        $varian = $query->paginate($perPage);

        foreach ($varian as $v) {
            $isDigital = ($v->tipeLayanan?->produk?->tipe_produk === 'digital');
            if ($isDigital) {
                $v->stok_tersedia = 999;
            } else {
                $v->stok_tersedia = StokAkun::where('id_varian', $v->id_varian)->where('status', StokStatus::TERSEDIA)->count();
            }
        }

        return $this->sendResponse($varian, 'Daftar varian berhasil diambil');
    }

    public function storeVarian(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $tipe = TipeLayanan::with('produk')->find($request->id_tipe);
        if (!$tipe || $tipe->produk?->id_toko != $toko->id_toko) {
            return $this->sendError('Tipe layanan tidak ditemukan atau bukan milik Anda', [], 403);
        }

        $isDigital = ($tipe->produk?->tipe_produk === 'digital');
        $limitMb = SettingKomisi::first()?->digital_file_limit_mb ?? 250;
        $limitKb = $limitMb * 1024;

        $rules = [
            'id_tipe' => 'required|exists:tbl_tipe_layanan,id_tipe',
            'nama_varian' => 'required|max:50',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:aktif,nonaktif',
        ];

        if ($isDigital) {
            $rules['file_digital'] = 'required|file|mimes:zip,rar,7z,tar,gz,pdf,epub,mobi,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,mp4,wav|max:' . $limitKb;
        } else {
            $rules['durasi_hari'] = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $data = [
            'id_tipe' => $request->id_tipe,
            'nama_varian' => $request->nama_varian,
            'harga' => $request->harga,
            'deskripsi' => $request->deskripsi,
            'status' => $request->status,
            'durasi_hari' => $isDigital ? 0 : (int) $request->durasi_hari,
        ];

        if ($isDigital && $request->hasFile('file_digital')) {
            $file = $request->file('file_digital');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . \Illuminate\Support\Str::random(32) . '.' . $extension;
            $targetDir = storage_path('app/private/file_digital');
            if (!file_exists($targetDir)) @mkdir($targetDir, 0755, true);
            $file->move($targetDir, $fileName);
            $data['file_path'] = $fileName;
        }

        $varian = VarianLayanan::create($data);

        return $this->sendResponse($varian, 'Varian layanan berhasil ditambahkan', 201);
    }

    public function updateVarian(Request $request, $id)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $varian = VarianLayanan::with('tipeLayanan.produk')->find($id);
        if (!$varian || $varian->tipeLayanan?->produk?->id_toko != $toko->id_toko) {
            return $this->sendError('Varian tidak ditemukan atau bukan milik Anda', [], 404);
        }

        $isDigital = ($varian->tipeLayanan?->produk?->tipe_produk === 'digital');
        $limitMb = SettingKomisi::first()?->digital_file_limit_mb ?? 250;
        $limitKb = $limitMb * 1024;

        $rules = [
            'nama_varian' => 'required|max:50',
            'harga' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:aktif,nonaktif',
        ];

        if ($isDigital) {
            $rules['file_digital'] = 'nullable|file|mimes:zip,rar,7z,tar,gz,pdf,epub,mobi,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,mp4,wav|max:' . $limitKb;
        } else {
            $rules['durasi_hari'] = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $varian->nama_varian = $request->nama_varian;
        $varian->harga = $request->harga;
        $varian->deskripsi = $request->deskripsi;
        $varian->status = $request->status;

        if (!$isDigital) {
            $varian->durasi_hari = (int) $request->durasi_hari;
        }

        if ($isDigital && $request->hasFile('file_digital')) {
            if ($varian->file_path) {
                $oldPrivate = storage_path('app/private/file_digital/' . $varian->file_path);
                $oldPublic = public_path('assets/file_digital/' . $varian->file_path);
                if (file_exists($oldPrivate)) @unlink($oldPrivate);
                if (file_exists($oldPublic)) @unlink($oldPublic);
            }
            $file = $request->file('file_digital');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = time() . '_' . \Illuminate\Support\Str::random(32) . '.' . $extension;
            $targetDir = storage_path('app/private/file_digital');
            if (!file_exists($targetDir)) @mkdir($targetDir, 0755, true);
            $file->move($targetDir, $fileName);
            $varian->file_path = $fileName;
        }

        $varian->save();

        return $this->sendResponse($varian, 'Varian layanan berhasil diperbarui');
    }

    public function destroyVarian(Request $request, $id)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $varian = VarianLayanan::with('tipeLayanan.produk')->find($id);
        if (!$varian || $varian->tipeLayanan?->produk?->id_toko != $toko->id_toko) {
            return $this->sendError('Varian tidak ditemukan atau bukan milik Anda', [], 404);
        }

        try {
            $hasPurchases = Pembelian::where('id_varian', $varian->id_varian)->exists();

            if ($hasPurchases) {
                StokAkun::where('id_varian', $varian->id_varian)->where('status', StokStatus::TERSEDIA)->delete();
                $varian->update(['status' => 'nonaktif']);
                return $this->sendResponse([], 'Varian memiliki riwayat transaksi, status diubah menjadi nonaktif.');
            }

            if ($varian->file_path) {
                $oldPrivate = storage_path('app/private/file_digital/' . $varian->file_path);
                $oldPublic = public_path('assets/file_digital/' . $varian->file_path);
                if (file_exists($oldPrivate)) @unlink($oldPrivate);
                if (file_exists($oldPublic)) @unlink($oldPublic);
            }

            StokAkun::where('id_varian', $varian->id_varian)->delete();
            $varian->delete();

            return $this->sendResponse([], 'Varian layanan berhasil dihapus');
        } catch (\Exception $e) {
            $varian->update(['status' => 'nonaktif']);
            return $this->sendResponse([], 'Varian diubah menjadi nonaktif demi keamanan transaksi.');
        }
    }

    // ==========================================
    // 3. STOK AKUN (CRUD for Premium)
    // ==========================================

    public function indexStok(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $idVarian = $request->input('id_varian');
        $status = $request->input('status');

        $query = StokAkun::whereHas('varianLayanan.tipeLayanan.produk', function($q) use ($toko) {
            $q->where('id_toko', $toko->id_toko);
        })->with(['varianLayanan.tipeLayanan.produk:id,nama_produk']);

        if ($idVarian) {
            $query->where('id_varian', $idVarian);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 20);
        $stok = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->sendResponse($stok, 'Daftar stok akun berhasil diambil');
    }

    public function storeStok(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $validator = Validator::make($request->all(), [
            'id_varian' => 'required|exists:tbl_varian_layanan,id_varian',
            'email_username' => 'required|string|max:150',
            'password' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $varian = VarianLayanan::where('id_varian', $request->id_varian)
            ->whereHas('tipeLayanan.produk', fn($q) => $q->where('id_toko', $toko->id_toko))
            ->first();

        if (!$varian) return $this->sendError('Varian tidak ditemukan atau bukan milik Anda', [], 403);

        $stok = StokAkun::create([
            'id_varian' => $request->id_varian,
            'email_username' => $request->email_username,
            'password_encrypted' => $request->password,
            'catatan' => $request->catatan,
            'status' => StokStatus::TERSEDIA,
        ]);

        return $this->sendResponse($stok, 'Stok akun berhasil ditambahkan', 201);
    }

    public function storeStokBulk(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $validator = Validator::make($request->all(), [
            'id_varian' => 'required|exists:tbl_varian_layanan,id_varian',
            'bulk_data' => 'required|string',
        ]);

        if ($validator->fails()) return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);

        $varian = VarianLayanan::where('id_varian', $request->id_varian)
            ->whereHas('tipeLayanan.produk', fn($q) => $q->where('id_toko', $toko->id_toko))
            ->first();

        if (!$varian) return $this->sendError('Varian tidak ditemukan atau bukan milik Anda', [], 403);

        $lines = explode("\n", str_replace("\r", "", $request->bulk_data));
        $count = 0;

        foreach ($lines as $line) {
            $parts = explode("|", trim($line));
            if (count($parts) >= 2 && !empty(trim($parts[0])) && !empty(trim($parts[1]))) {
                StokAkun::create([
                    'id_varian' => $request->id_varian,
                    'email_username' => trim($parts[0]),
                    'password_encrypted' => trim($parts[1]),
                    'catatan' => isset($parts[2]) ? trim($parts[2]) : null,
                    'status' => StokStatus::TERSEDIA,
                ]);
                $count++;
            }
        }

        return $this->sendResponse(['total_added' => $count], "Berhasil menambahkan {$count} stok akun secara bulk", 201);
    }

    public function decryptStok(Request $request, $id)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $stok = StokAkun::with('varianLayanan.tipeLayanan.produk')->find($id);
        if (!$stok || $stok->varianLayanan?->tipeLayanan?->produk?->id_toko != $toko->id_toko) {
            return $this->sendError('Stok tidak ditemukan atau bukan milik Anda', [], 404);
        }

        return $this->sendResponse([
            'id_stok' => $stok->id_stok,
            'email_username' => $stok->email_username,
            'password' => $stok->password_encrypted,
            'catatan' => $stok->catatan,
            'status' => $stok->status->value ?? $stok->status,
        ], 'Kredensial stok berhasil diambil');
    }

    public function destroyStok(Request $request, $id)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $stok = StokAkun::with('varianLayanan.tipeLayanan.produk')->find($id);
        if (!$stok || $stok->varianLayanan?->tipeLayanan?->produk?->id_toko != $toko->id_toko) {
            return $this->sendError('Stok tidak ditemukan atau bukan milik Anda', [], 404);
        }

        if ($stok->status === StokStatus::TERJUAL) {
            return $this->sendError('Stok yang sudah terjual tidak dapat dihapus', [], 400);
        }

        $stok->delete();

        return $this->sendResponse([], 'Stok akun berhasil dihapus');
    }

    // ==========================================
    // 4. HISTORI PENJUALAN SELLER
    // ==========================================

    public function historiPenjualan(Request $request)
    {
        $toko = $this->getSellerToko($request);
        if (!$toko) return $this->sendError('Toko tidak ditemukan', [], 404);

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');

        $query = Pembelian::whereHas('varianLayanan.tipeLayanan.produk', function($q) use ($toko) {
                $q->where('id_toko', $toko->id_toko);
            })
            ->with(['varianLayanan.tipeLayanan.produk', 'customer.user:id,name,email', 'pembayaran'])
            ->where('status', PembelianStatus::SUCCESS);

        if ($startDateInput && $endDateInput) {
            try {
                $startDate = \Illuminate\Support\Carbon::parse($startDateInput)->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($endDateInput)->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } catch (\Exception $e) {
                return $this->sendError('Format tanggal tidak valid', [], 400);
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        $penjualan = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->sendResponse($penjualan, 'Histori penjualan seller berhasil diambil');
    }
}
