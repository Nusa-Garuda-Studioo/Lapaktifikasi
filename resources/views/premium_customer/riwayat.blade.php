@extends('layout')

@section('title', 'Riwayat Pembelian Premium')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/customer-history.css') }}">
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

    <div class="riwayat-container">
        <h4 class="riwayat-header-title">Riwayat Pembelian</h4>
        
        <div class="tab-nav-container mb-4">
            <a href="{{ route('premium.riwayat', request()->except('status', 'page')) }}" class="riwayat-tab-btn {{ !request('status') ? 'active' : '' }}">Semua</a>
            <a href="{{ route('premium.riwayat', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" class="riwayat-tab-btn {{ request('status') == 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ route('premium.riwayat', array_merge(request()->except('status', 'page'), ['status' => 'success'])) }}" class="riwayat-tab-btn {{ request('status') == 'success' ? 'active' : '' }}">Sukses</a>
            <a href="{{ route('premium.riwayat', array_merge(request()->except('status', 'page'), ['status' => 'failed'])) }}" class="riwayat-tab-btn {{ in_array(request('status'), ['failed', 'expired', 'cancelled']) ? 'active' : '' }}">Gagal / Batal</a>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="mono-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 pb-2 border-bottom" style="gap: 15px;">
                        <h4 class="form-title mb-0" style="border-bottom: none; padding-bottom: 0;">Riwayat Pembelian Akun Premium Anda</h4>
                        
                        <!-- Date Filter Form -->
                        <form action="{{ route('premium.riwayat') }}" method="GET" class="form-inline w-100 w-sm-auto justify-content-start justify-content-sm-end" style="gap: 8px;">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            <div class="form-group mb-2 mr-2">
                                <label for="start_date" class="sr-only">Dari</label>
                                <input type="date" class="form-control form-control-sm border-dark text-dark" id="start_date" name="start_date" value="{{ request('start_date') }}" style="border-radius: 6px; font-size: 0.85rem; height: 38px;">
                            </div>
                            <div class="form-group mb-2 mr-2">
                                <span class="text-muted px-1" style="font-size: 0.85rem;">s/d</span>
                            </div>
                            <div class="form-group mb-2 mr-2">
                                <label for="end_date" class="sr-only">Sampai</label>
                                <input type="date" class="form-control form-control-sm border-dark text-dark" id="end_date" name="end_date" value="{{ request('end_date') }}" style="border-radius: 6px; font-size: 0.85rem; height: 38px;">
                            </div>
                            <button type="submit" class="btn btn-sm btn-dark mb-2 mr-1" style="border-radius: 6px; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; height: 38px; padding: 0 16px;">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            @if(request('start_date') || request('end_date'))
                                <a href="{{ route('premium.riwayat', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-sm btn-outline-secondary mb-2" style="border-radius: 6px; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 0 16px;">
                                    Reset
                                </a>
                            @endif
                        </form>
                    </div>
                    
                    <!-- Desktop Table (Hidden on Mobile) -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table mono-table" id="table-1">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Order ID</th>
                                        <th>Produk / Paket</th>
                                        <th>Harga Beli</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pembelian as $index => $item)
                                    @php
                                        $isPendingActive = ($item->status->value == 'pending') && (!$item->reserved_until || $item->reserved_until > now());
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td style="font-size: 0.82rem; white-space: nowrap;">{{ $item->created_at->translatedFormat('d F Y, H:i') }}</td>
                                        <td><code style="font-size: 0.75rem; white-space: nowrap;">{{ $item->order_id }}</code></td>
                                        <td style="min-width: 180px;">
                                            <strong class="d-block text-dark" style="font-size: 0.88rem;">{{ $item->varianLayanan?->tipeLayanan?->produk?->nama_produk }}</strong>
                                            <small class="text-muted">{{ $item->varianLayanan?->tipeLayanan?->nama_tipe }} ({{ $item->varianLayanan?->nama_varian }})</small>
                                        </td>
                                        <td style="white-space: nowrap; font-weight: 700;">Rp {{ number_format($item->harga_saat_beli, 0, ',', '.') }}</td>
                                        <td>
                                            @if($isPendingActive)
                                            <span class="mono-badge mono-badge-pending">Pending</span>
                                            @elseif($item->status->value == 'success')
                                            <span class="mono-badge mono-badge-success">Success</span>
                                            @elseif($item->status->value == 'expired' || $item->status->value == 'cancelled' || ($item->status->value == 'pending' && $item->reserved_until && $item->reserved_until <= now()))
                                            <span class="mono-badge mono-badge-expired">Transaksi Dibatalkan</span>
                                            @else
                                            <span class="mono-badge mono-badge-failed">Failed</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isPendingActive)
                                            <a href="{{ route('metode_pembayaran', $item->order_id) }}" class="btn btn-sm btn-dark font-weight-bold" style="border-radius: 6px; font-size: 0.75rem; padding: 6px 14px; white-space: nowrap;">
                                                <i class="bi bi-credit-card-fill mr-1"></i> Selesaikan
                                            </a>
                                            @elseif($item->status->value == 'success')
                                            <div class="table-action-group">
                                                {{-- Tombol Utama Primary --}}
                                                @if($item->varianLayanan?->tipeLayanan?->produk?->tipe_produk == 'digital')
                                                <a href="{{ route('premium.digital.download', $item->order_id) }}" class="btn btn-sm btn-dark font-weight-bold" style="border-radius: 6px; font-size: 0.75rem; padding: 6px 12px; white-space: nowrap; display: inline-flex; align-items: center;">
                                                    <i class="bi bi-download mr-1"></i> Download
                                                </a>
                                                @else
                                                <button class="btn btn-sm btn-dark font-weight-bold" onclick="viewCredentials('{{ $item->order_id }}')" style="border-radius: 6px; font-size: 0.75rem; padding: 6px 12px; white-space: nowrap; display: inline-flex; align-items: center;">
                                                    <i class="bi bi-key-fill mr-1"></i> Lihat Akun
                                                </button>
                                                @endif

                                                {{-- Tombol Sekunder Outline --}}
                                                <a href="{{ route('premium.invoice.download', $item->order_id) }}" class="btn btn-sm btn-outline-secondary font-weight-bold" target="_blank" title="Unduh Invoice" style="border-radius: 6px; font-size: 0.75rem; padding: 6px 10px; white-space: nowrap; display: inline-flex; align-items: center;">
                                                    <i class="bi bi-file-earmark-pdf mr-1"></i> Invoice
                                                </a>
                                                @if(!$item->review)
                                                <a href="{{ route('premium.review.show', $item->order_id) }}" class="btn btn-sm btn-outline-secondary font-weight-bold" title="Beri Ulasan" style="border-radius: 6px; font-size: 0.75rem; padding: 6px 10px; white-space: nowrap; display: inline-flex; align-items: center;">
                                                    <i class="bi bi-star-fill text-warning mr-1"></i> Ulas
                                                </a>
                                                @endif
                                            </div>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Anda belum memiliki transaksi pembelian akun premium.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile List (Hidden on Desktop) -->
                    <div class="d-md-none">
                        @forelse($pembelian as $index => $item)
                        @php
                            $isPendingActiveMobile = ($item->status->value == 'pending') && (!$item->reserved_until || $item->reserved_until > now());
                        @endphp
                        <div class="card mb-3 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff;">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 px-3 border-bottom" style="border-radius: 12px 12px 0 0;">
                                <div class="d-flex align-items-center gap-2" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <i class="bi bi-shop text-muted"></i>
                                    <span class="font-weight-bold text-dark" style="font-size: clamp(0.75rem, 2vw, 0.85rem);">{{ $item->varianLayanan?->tipeLayanan?->produk?->toko?->nama_toko ?? 'Toko' }}</span>
                                </div>
                                <span class="text-right" style="flex-shrink: 0; padding-left: 10px;">
                                    @if($isPendingActiveMobile)
                                    <span class="mono-badge mono-badge-pending" style="font-size: 0.65rem !important;">Pending</span>
                                    @elseif($item->status->value == 'success')
                                    <span class="mono-badge mono-badge-success" style="font-size: 0.65rem !important;">Success</span>
                                    @elseif(in_array($item->status->value, ['expired', 'cancelled']) || ($item->status->value == 'pending' && $item->reserved_until && $item->reserved_until <= now()))
                                    <span class="mono-badge mono-badge-expired" style="font-size: 0.65rem !important;">Dibatalkan</span>
                                    @else
                                    <span class="mono-badge mono-badge-failed" style="font-size: 0.65rem !important;">Failed</span>
                                    @endif
                                </span>
                            </div>
                            
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong class="text-dark d-block" style="font-size: clamp(0.9rem, 3vw, 1rem); line-height: 1.3;">
                                        {{ $item->varianLayanan?->tipeLayanan?->produk?->nama_produk }}
                                    </strong>
                                    <span class="font-weight-bold text-dark ml-2">x1</span>
                                </div>
                                <span class="text-muted d-block mb-2" style="font-size: clamp(0.75rem, 2.5vw, 0.85rem);">
                                    Varian: {{ $item->varianLayanan?->tipeLayanan?->nama_tipe }} ({{ $item->varianLayanan?->nama_varian }})
                                </span>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                                    <span>{{ $item->created_at->translatedFormat('d F Y, H:i') }}</span>
                                    <code>{{ $item->order_id }}</code>
                                </div>
                            </div>

                            <!-- Mobile Action Buttons & Total -->
                            <div class="card-footer bg-white px-3 py-3 d-flex flex-column gap-2" style="border-radius: 0 0 12px 12px; border-top: 1px dashed #e5e5e5;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-dark font-weight-bold" style="font-size: 0.85rem;">Total Pesanan</span>
                                    <span class="font-weight-bold text-dark" style="font-size: clamp(1rem, 4vw, 1.15rem);">Rp {{ number_format($item->harga_saat_beli, 0, ',', '.') }}</span>
                                </div>
                                
                                <div class="d-flex flex-column gap-2 w-100">
                                    @if($isPendingActiveMobile)
                                    <a href="{{ route('metode_pembayaran', $item->order_id) }}" class="btn btn-dark font-weight-bold w-100" style="border-radius: 6px; font-size: 0.75rem; text-transform: uppercase; min-height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-credit-card-fill mr-1"></i> Selesaikan
                                    </a>
                                    @elseif($item->status->value == 'success')
                                        {{-- Primary Action Button --}}
                                        @if($item->varianLayanan?->tipeLayanan?->produk?->tipe_produk == 'digital')
                                        <a href="{{ route('premium.digital.download', $item->order_id) }}" class="btn btn-dark font-weight-bold w-100" style="border-radius: 6px; font-size: 0.75rem; text-transform: uppercase; min-height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-download mr-1"></i> Download File Digital
                                        </a>
                                        @else
                                        <button class="btn btn-dark font-weight-bold w-100" onclick="viewCredentials('{{ $item->order_id }}')" style="border-radius: 6px; font-size: 0.75rem; text-transform: uppercase; min-height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-key-fill mr-1"></i> Lihat Akun
                                        </button>
                                        @endif

                                        {{-- Secondary Action Buttons --}}
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('premium.invoice.download', $item->order_id) }}" class="btn btn-outline-dark font-weight-bold flex-fill" target="_blank" style="border-radius: 6px; font-size: 0.75rem; text-transform: uppercase; min-height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-file-earmark-pdf-fill mr-1"></i> Invoice
                                            </a>
                                            @if(!$item->review)
                                            <a href="{{ route('premium.review.show', $item->order_id) }}" class="btn btn-outline-dark font-weight-bold flex-fill" style="border-radius: 6px; font-size: 0.75rem; text-transform: uppercase; min-height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-star-fill text-warning mr-1"></i> Ulas
                                            </a>
                                            @endif
                                        </div>
                                    @else
                                    <div class="text-center text-muted" style="font-size: 0.85rem;">-</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted bg-light rounded" style="border: 1px dashed #ccc; border-radius: 8px;">
                            Anda belum memiliki transaksi pembelian akun premium.
                        </div>
                        @endforelse

                        @if(method_exists($pembelian, 'hasPages') && $pembelian->hasPages())
                        <div class="d-flex justify-content-center mt-4 pt-2">
                            {{ $pembelian->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tampilkan Kredensial -->
    <div class="modal fade" id="kredensialModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kredensial Akun Premium</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mono-modal-alert">
                        <p class="mb-0"><strong>Penting:</strong> Harap simpan informasi akun ini dengan aman. Jangan menyebarkan informasi akun kepada pihak lain.</p>
                    </div>
                    <div class="form-group">
                        <label class="mono-input-label">Username / Email</label>
                        <div class="input-group">
                            <input type="text" class="form-control mono-input-val" id="kred-email" readonly style="min-height: 44px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; font-size: clamp(0.85rem, 3vw, 1rem);">
                            <div class="input-group-append">
                                <button class="btn mono-btn-outline" type="button" onclick="copyText('kred-email')" style="min-height: 44px; display: inline-flex; align-items: center; justify-content: center; padding: 0 20px;">Salin</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="mono-input-label">Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control mono-input-val" id="kred-password" readonly style="min-height: 44px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; font-size: clamp(0.85rem, 3vw, 1rem);">
                            <div class="input-group-append">
                                <button class="btn mono-btn-outline" type="button" onclick="copyText('kred-password')" style="min-height: 44px; display: inline-flex; align-items: center; justify-content: center; padding: 0 20px;">Salin</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="mono-input-label">Catatan Akses Layanan</label>
                        <textarea class="form-control mono-input-val" style="height: auto;" id="kred-catatan" rows="3" readonly></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn mono-btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewCredentials(orderId) {
            fetch("{{ url('premium/kredensial') }}/" + orderId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Akses kredensial gagal.");
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('kred-email').value = data.email_username;
                    document.getElementById('kred-password').value = data.password;
                    document.getElementById('kred-catatan').value = data.catatan ? data.catatan : '-';
                    $('#kredensialModal').modal('show');
                })
                .catch(error => {
                    Swal.fire({ title: "Akses Ditolak", text: "Gagal memuat kredensial akun.", icon: "error" });
                });
        }

        function copyText(id) {
            const copyText = document.getElementById(id);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 1500
            });
            Toast.fire({ icon: "success", title: "Berhasil disalin!" });
        }
    </script>

@endsection
