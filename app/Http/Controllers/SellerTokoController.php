<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Toko;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class SellerTokoController extends Controller
{
    public function index()
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
        return view('seller.profil', compact('toko'));
    }

    public function update(Request $request)
    {
        $toko = Toko::where('user_id', Auth::id())->firstOrFail();

        $rules = [
            'nama_toko' => 'required|string|max:150',
            'no_telp' => 'required|string|max:20',
            'akun_telegram' => 'required|string|max:100',
            'informasi_toko' => 'nullable|string',
            'logo_toko' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        $messages = [
            'nama_toko.required' => 'Nama Toko harus diisi.',
            'no_telp.required' => 'Nomor Telepon harus diisi.',
            'akun_telegram.required' => 'Akun Telegram harus diisi.',
            'logo_toko.image' => 'Logo harus berupa gambar.',
            'logo_toko.mimes' => 'Format gambar logo harus jpeg, png, jpg, atau webp.',
            'logo_toko.max' => 'Ukuran gambar logo maksimal 2MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect('/seller/profil')
                ->withErrors($validator)
                ->withInput();
        }

        $logoName = $toko->logo_toko;
        if ($request->hasFile('logo_toko')) {
            // Delete old logo if exists
            if ($toko->logo_toko && file_exists(public_path('assets/img/logo_toko/' . $toko->logo_toko))) {
                @unlink(public_path('assets/img/logo_toko/' . $toko->logo_toko));
            }

            $file = $request->file('logo_toko');
            $logoName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/img/logo_toko'), $logoName);
        }

        $namaToko = $request->input('nama_toko');
        $slug = $toko->slug ?: Toko::generateUniqueSlug($namaToko, $toko->id_toko);

        $toko->update([
            'nama_toko' => $namaToko,
            'slug' => $slug,
            'no_telp' => $request->input('no_telp'),
            'akun_telegram' => $request->input('akun_telegram'),
            'informasi_toko' => $request->input('informasi_toko'),
            'logo_toko' => $logoName,
        ]);

        Session::flash('success', 'Profil toko Anda berhasil diperbarui.');
        return redirect('/seller/profil');
    }

    public function badges()
    {
        $user = Auth::user();
        $toko = Toko::with('badges')->where('user_id', $user->id)->first();
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
            $toko->load('badges');
        }
        $allBadges = \App\Models\SellerBadge::all();

        $rating = (float) ($toko->rating_rata_rata ?? 0);
        $lamaBergabungHari = $toko->created_at ? (int) now()->diffInDays($toko->created_at) : 0;
        $volumeTransaksi = \App\Models\Pembelian::whereHas('varianLayanan.tipeLayanan.produk', function ($q) use ($toko) {
            $q->where('id_toko', $toko->id_toko);
        })->where('status', \App\Enums\PembelianStatus::SUCCESS)->count();

        $ownedBadgeIds = $toko->badges->pluck('id_badge')->toArray();

        $badgeProgress = [];
        foreach ($allBadges as $b) {
            $isOwned = in_array($b->id_badge, $ownedBadgeIds);
            $threshold = (float) $b->kriteria_nilai;
            $currentVal = 0;
            $progressText = '';
            $percent = 0;

            switch ($b->kriteria_tipe) {
                case 'rating_minimal':
                    $currentVal = $rating;
                    $percent = min(100, round(($rating / max(1, $threshold)) * 100));
                    $sisa = max(0, round($threshold - $rating, 2));
                    $progressText = $isOwned ? 'Kriteria terpenuhi!' : "Rating saat ini {$rating}/{$threshold} (butuh +{$sisa} rating)";
                    break;
                case 'lama_bergabung':
                    $currentVal = $lamaBergabungHari;
                    $percent = min(100, round(($lamaBergabungHari / max(1, $threshold)) * 100));
                    $sisa = max(0, (int) $threshold - $lamaBergabungHari);
                    $progressText = $isOwned ? 'Kriteria terpenuhi!' : "Bergabung {$lamaBergabungHari}/{$threshold} hari (butuh {$sisa} hari lagi)";
                    break;
                case 'volume_transaksi':
                    $currentVal = $volumeTransaksi;
                    $percent = min(100, round(($volumeTransaksi / max(1, $threshold)) * 100));
                    $sisa = max(0, (int) $threshold - $volumeTransaksi);
                    $progressText = $isOwned ? 'Kriteria terpenuhi!' : "{$volumeTransaksi}/{$threshold} transaksi sukses (butuh {$sisa} transaksi lagi)";
                    break;
                default:
                    $progressText = 'Fitur tracking otomatis kriteria ini belum tersedia.';
                    $percent = $isOwned ? 100 : 0;
                    break;
            }

            $badgeProgress[] = [
                'badge' => $b,
                'is_owned' => $isOwned,
                'progress_text' => $progressText,
                'percent' => $percent,
                'diperoleh_pada' => $isOwned ? $toko->badges->where('id_badge', $b->id_badge)->first()?->pivot?->diperoleh_pada : null,
            ];
        }

        return view('seller.badges', compact('toko', 'badgeProgress'));
    }
}
