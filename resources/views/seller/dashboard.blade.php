@extends('layout')

@section('title', 'Dashboard Seller: ' . $toko->nama_toko)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/seller-dashboard.css') }}">
@endpush

@section('content')

@if($success = Session::get('success'))
<script>
    Swal.fire({ title: "Berhasil", text: "{{ $success }}", icon: "success" });
</script>
@endif

@if($error = Session::get('error'))
<script>
    Swal.fire({ title: "Gagal", text: "{{ $error }}", icon: "error" });
</script>
@endif

<x-page-header title="Dashboard {{ $toko->nama_toko }}" subtitle="Kelola statistik toko, penjualan, dan saldo mutasi toko Anda.">
    <x-slot:actions>
        <a href="{{ url('seller/profil') }}" class="px-5 py-2.5 rounded-full text-xs font-semibold text-indigo-950 bg-white border border-gray-200 hover:bg-gray-50 transition-colors shadow-sm">
            Profil Toko
        </a>
        <a href="{{ route('seller.voucher.index') }}" class="px-5 py-2.5 rounded-full text-xs font-semibold text-white bg-violet-700 hover:bg-violet-800 transition-colors shadow-sm">
            Voucher Toko
        </a>
    </x-slot:actions>
</x-page-header>

@php
    $sellerUser = Auth::user();
@endphp

@if($sellerUser && $sellerUser->telegram_blocked_at)
    <!-- Blocked Banner -->
    <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 flex items-start gap-3 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill text-2xl text-red-600 mt-0.5"></i>
        <div class="flex-1">
            <h5 class="text-sm font-bold text-red-800 uppercase tracking-wider mb-1">Bot Telegram Diblokir!</h5>
            <p class="text-xs text-red-700 leading-relaxed mb-3">
                Anda memblokir bot Telegram Lapaktifikasi pada {{ $sellerUser->telegram_blocked_at->translatedFormat('d M Y, H:i') }} WIB. Status bot yang terblokir akan menyebabkan toko dan produk Anda dinonaktifkan dari katalog publik. Harap segera buka blokir bot kami di aplikasi Telegram Anda.
            </p>
            <a href="{{ url('/profile_customer/' . $sellerUser->id) }}#telegram-section" class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors">
                <i class="bi bi-gear-fill"></i> Kelola Tautan Telegram
            </a>
        </div>
    </div>
@elseif($sellerUser && !$sellerUser->hasTelegram())
    <!-- Unlinked / Grace Period Banner -->
    <div class="mb-6 p-4 rounded-2xl {{ $toko->status === 'nonaktif' ? 'bg-red-50 border-red-200 text-red-900' : 'bg-amber-50 border-amber-200 text-amber-900' }} border flex items-start gap-3 shadow-sm">
        <i class="bi {{ $toko->status === 'nonaktif' ? 'bi-shield-slash-fill text-red-600' : 'bi-info-circle-fill text-amber-600' }} text-2xl mt-0.5"></i>
        <div class="flex-1">
            <h5 class="text-sm font-bold {{ $toko->status === 'nonaktif' ? 'text-red-800' : 'text-amber-800' }} uppercase tracking-wider mb-1">
                {{ $toko->status === 'nonaktif' ? 'Wajib Menautkan Telegram untuk Mengaktifkan Toko' : 'Pengingat: Wajib Menautkan Akun Telegram Toko' }}
            </h5>
            <p class="text-xs leading-relaxed mb-3 {{ $toko->status === 'nonaktif' ? 'text-red-700' : 'text-amber-700' }}">
                @if($toko->status === 'nonaktif')
                    Toko Anda saat ini <strong>Nonaktif</strong> dan produk belum dapat tayang di katalog publik. Tautkan akun Telegram Anda sekarang untuk langsung mengaktifkan toko dan produk Anda.
                @else
                    Sesuai kebijakan keamanan platform, mitra seller wajib menautkan bot Telegram. 
                    Batas masa tenggang Anda: <strong>{{ $toko->telegram_grace_due_at ? $toko->telegram_grace_due_at->translatedFormat('d F Y, H:i') : '14 hari' }}</strong> 
                    ({{ $toko->telegram_grace_due_at ? ($toko->telegram_grace_due_at->isPast() ? 'Sudah lewat' : 'sisa ' . $toko->telegram_grace_due_at->diffForHumans(null, true)) : '' }}).
                    Setelah melewati batas ini, toko dan seluruh produk Anda otomatis dinonaktifkan.
                @endif
            </p>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ url('/profile_customer/' . $sellerUser->id) }}#telegram-section" class="inline-flex items-center gap-1.5 px-4 py-1.5 {{ $toko->status === 'nonaktif' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-700 hover:bg-amber-800' }} text-white text-xs font-bold rounded-lg transition-colors">
                    <i class="bi bi-telegram"></i> Tautkan Telegram Sekarang
                </a>
            </div>
        </div>
    </div>
@endif

<!-- Time Range Selector Form -->
<div class="seller-filter-card mb-8">
    <form action="{{ url('seller/dashboard') }}" method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <label for="filter_range" class="font-bold text-indigo-950 text-sm mb-0">Rentang Waktu:</label>
            <select name="filter_range" id="filter_range" class="seller-filter-input bg-white text-gray-800 font-medium focus:outline-none focus:border-violet-600" onchange="toggleCustomDates(this.value)">
                <option value="today" {{ request('filter_range') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                <option value="7_days" {{ request('filter_range', 'today') == '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                <option value="this_month" {{ request('filter_range') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                <option value="custom" {{ request('filter_range') == 'custom' ? 'selected' : '' }}>Rentang Custom</option>
            </select>
        </div>
        
        <div id="custom-date-inputs" class="flex items-center gap-2" style="display: {{ request('filter_range') == 'custom' ? 'flex' : 'none' }};">
            <input type="date" class="seller-filter-input" name="start_date" value="{{ request('start_date') }}">
            <span class="text-xs text-gray-400">s/d</span>
            <input type="date" class="seller-filter-input" name="end_date" value="{{ request('end_date') }}">
        </div>
        
        <button type="submit" class="px-5 py-2 bg-indigo-950 hover:bg-indigo-900 text-white text-xs font-bold rounded-lg uppercase tracking-wider transition-colors">
            Terapkan Filter
        </button>
    </form>
</div>

<script>
    function toggleCustomDates(val) {
        document.getElementById('custom-date-inputs').style.display = val === 'custom' ? 'flex' : 'none';
    }
</script>

<!-- Stat Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <x-stat-card 
        title="{{ $range === 'today' ? 'Order Sukses Hari Ini' : 'Order Sukses' }}" 
        value="{{ $total_order }}" 
        icon="bi bi-cart-check"
        iconBg="bg-emerald-600"
        badgeText="Berhasil"
        badgeClass="text-emerald-700 bg-emerald-50"
    />

    <x-stat-card 
        title="{{ $range === 'today' ? 'Omzet Hari Ini' : 'Omzet Toko' }}" 
        value="Rp {{ is_numeric($total_penjualan) ? number_format($total_penjualan, 0, ',', '.') : $total_penjualan }}" 
        icon="bi bi-cash-stack"
        iconBg="bg-indigo-950"
        badgeText="Pendapatan"
        badgeClass="text-indigo-700 bg-indigo-50"
    />

    <x-stat-card 
        title="Saldo Toko Saat Ini" 
        value="Rp {{ number_format($saldo_toko, 0, ',', '.') }}" 
        icon="bi bi-wallet2"
        iconBg="bg-violet-700"
        badgeText="Saldo"
        badgeClass="text-violet-700 bg-violet-50"
    />

    <x-stat-card 
        title="Total Produk Terjual" 
        value="{{ $total_produk_terjual }}" 
        icon="bi bi-box-seam"
        iconBg="bg-amber-600"
        badgeText="Unit"
        badgeClass="text-amber-700 bg-amber-50"
    />
</div>

<!-- Support Tickets Section -->
<div class="mb-8">
    <x-card title="Tiket Kendala & Garansi Pelanggan" subtitle="Laporan masalah dari customer terkait pesanan produk toko Anda">
        <div class="dash-table-wrapper overflow-x-auto">
            <table class="table dash-table table-hover w-full">
                <thead>
                    <tr>
                        <th>No Tiket</th>
                        <th>Customer</th>
                        <th>Judul Kendala</th>
                        <th>Waktu Masuk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tiket_kendala ?? [] as $t)
                    <tr>
                        <td class="font-bold text-indigo-950">#{{ str_pad($t->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="text-xs text-gray-700 font-medium">
                            {{ $t->user?->name ?: 'Customer' }}
                            <small class="text-gray-400 block">{{ $t->user?->email ?? '-' }}</small>
                        </td>
                        <td class="text-xs font-semibold text-gray-800">{{ \Illuminate\Support\Str::limit($t->judul, 40) }}</td>
                        <td class="text-xs text-gray-500">{{ $t->created_at->format('d M Y H:i') }}</td>
                        <td>
                            @if($t->status === 'pending')
                                <span class="dash-badge dash-badge-warning">Pending</span>
                            @elseif($t->status === 'proses')
                                <span class="dash-badge dash-badge-info">Diproses</span>
                            @else
                                <span class="dash-badge dash-badge-success">Selesai</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('laporan.show', $t->id) }}" class="px-3 py-1.5 bg-indigo-950 hover:bg-indigo-900 text-white rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                                <i class="bi bi-chat-dots-fill"></i> Buka Thread
                                @if($t->pesan && $t->pesan->count() > 0)
                                    <span class="ml-1 px-1.5 py-0.2 bg-white text-indigo-950 rounded-full text-xs font-bold">{{ $t->pesan->count() }}</span>
                                @endif
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-6">Belum ada laporan kendala customer untuk toko Anda.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

<!-- Mutation Logs Section -->
<x-card title="Riwayat Mutasi Saldo Terbaru" subtitle="Mutasi kredit dan debit saldo toko Anda">
    <x-slot:headerAction>
        <a href="{{ url('seller/mutasi') }}" class="px-4 py-2 bg-violet-700 hover:bg-violet-800 text-white rounded-full text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
            <i class="bi bi-list"></i> Lihat Semua Mutasi
        </a>
    </x-slot:headerAction>

    <div class="dash-table-wrapper overflow-x-auto">
        <table class="table dash-table table-hover w-full">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Nominal</th>
                    <th>Saldo Akhir</th>
                    <th>Keterangan</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat_mutasi as $log)
                <tr>
                    <td class="text-xs text-gray-500 font-medium">{{ $log->created_at ? $log->created_at->format('d M Y H:i') : '-' }}</td>
                    <td>
                        @if($log->tipe == 'kredit_penjualan')
                            <span class="dash-badge dash-badge-success">Kredit Penjualan</span>
                        @elseif($log->tipe == 'potong_withdraw')
                            <span class="dash-badge dash-badge-danger">Potong Withdraw</span>
                        @elseif($log->tipe == 'penyesuaian_admin')
                            <span class="dash-badge dash-badge-warning">Penyesuaian Admin</span>
                        @else
                            <span class="dash-badge dash-badge-info">{{ $log->tipe }}</span>
                        @endif
                    </td>
                    <td class="font-bold {{ $log->nominal > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $log->nominal > 0 ? '+' : '' }}Rp {{ number_format($log->nominal, 0, ',', '.') }}
                    </td>
                    <td class="font-semibold text-indigo-950">Rp {{ number_format($log->saldo_akhir, 0, ',', '.') }}</td>
                    <td class="text-xs text-gray-600">{{ $log->keterangan ?? '-' }}</td>
                    <td class="text-xs text-gray-400">
                        @if($log->tipe == 'kredit_penjualan')
                            Sistem
                        @else
                            {{ $log->dibuatOleh->name ?? 'Admin' }}
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-400 py-6">Belum ada riwayat mutasi saldo.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@endsection
