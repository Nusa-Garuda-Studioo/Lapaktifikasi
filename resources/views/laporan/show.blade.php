@extends('layout')

@section('title', 'Thread Tiket #' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT))

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

@php
    $currentUser = Auth::user();
    $ticketNumber = '#' . str_pad($laporan->id, 5, '0', STR_PAD_LEFT);
@endphp

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            @if($currentUser->isAdmin())
                <a href="{{ route('admin.laporan') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Laporan Admin
                </a>
            @elseif($currentUser->isSeller())
                <a href="{{ route('seller.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Dashboard Seller
                </a>
            @else
                <a href="{{ route('customer.laporan') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Riwayat Tiket
                </a>
            @endif
        </div>
        <div>
            @if($laporan->status === 'pending')
                <span class="badge badge-warning px-3 py-2" style="font-size: 0.9rem;">
                    <i class="bi bi-clock-history mr-1"></i> Status: Menunggu Penanganan (Pending)
                </span>
            @elseif($laporan->status === 'proses')
                <span class="badge badge-primary px-3 py-2" style="font-size: 0.9rem;">
                    <i class="bi bi-gear-fill mr-1"></i> Status: Sedang Diproses
                </span>
            @else
                <span class="badge badge-success px-3 py-2" style="font-size: 0.9rem;">
                    <i class="bi bi-check-circle-fill mr-1"></i> Status: Selesai
                </span>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <!-- Kolom Kiri: Informasi Tiket -->
    <div class="col-12 col-lg-4 mb-4">
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 font-weight-bold text-dark">
                    <i class="bi bi-info-circle text-primary mr-1"></i> Informasi Tiket {{ $ticketNumber }}
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Judul Kendala:</small>
                    <strong class="text-dark">{{ $laporan->judul }}</strong>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Pelapor (Customer):</small>
                    <strong>{{ $laporan->user->name ?: 'Customer' }}</strong>
                    <span class="text-muted small d-block">{{ $laporan->user->email }}</span>
                </div>

                @if(!empty($context['order_id']))
                <div class="mb-3">
                    <small class="text-muted d-block">Order ID Terkait:</small>
                    <code class="font-weight-bold">{{ $context['order_id'] }}</code>
                </div>
                @endif

                @if(!empty($context['produk_nama']))
                <div class="mb-3">
                    <small class="text-muted d-block">Produk:</small>
                    <span>{{ $context['produk_nama'] }}</span>
                </div>
                @endif

                @if(!empty($context['toko_nama']))
                <div class="mb-3">
                    <small class="text-muted d-block">Toko Seller:</small>
                    <span class="badge badge-light border">{{ $context['toko_nama'] }}</span>
                </div>
                @endif

                <hr>

                <div class="mb-2">
                    <small class="text-muted d-block">Waktu Dibuat:</small>
                    <small class="text-dark">{{ $laporan->created_at->format('d M Y, H:i') }} WIB</small>
                </div>

                @if($laporan->sla_due_at)
                <div class="mb-2">
                    <small class="text-muted d-block">Batas Target Penanganan (SLA):</small>
                    <small class="text-dark font-weight-bold">
                        <i class="bi bi-alarm mr-1"></i>{{ $laporan->sla_due_at->format('d M Y, H:i') }} WIB
                    </small>
                </div>
                @endif

                @if($laporan->first_response_at)
                <div class="mb-2">
                    <small class="text-muted d-block">Respon Pertama (Seller/Admin):</small>
                    <small class="text-success">
                        <i class="bi bi-check2-all mr-1"></i>{{ $laporan->first_response_at->format('d M Y, H:i') }} WIB
                    </small>
                </div>
                @endif

                @if($laporan->gambar)
                <hr>
                <div>
                    <small class="text-muted d-block mb-1">Lampiran Awal Tiket:</small>
                    <a href="{{ asset('assets/img/laporan/' . $laporan->gambar) }}" target="_blank">
                        <img src="{{ asset('assets/img/laporan/' . $laporan->gambar) }}" alt="Lampiran Awal" class="img-fluid rounded border" style="max-height: 140px;">
                    </a>
                </div>
                @endif
            </div>
        </div>

        @if($currentUser->isAdmin())
        <div class="card shadow-sm border">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 font-weight-bold">
                    <i class="bi bi-sliders mr-1 text-primary"></i> Kelola Status (Admin)
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.laporan.status', $laporan->id) }}" method="POST">
                    @csrf
                    <div class="form-group mb-2">
                        <label class="small text-muted mb-1">Ubah Status Tiket:</label>
                        <select name="status" class="custom-select custom-select-sm">
                            <option value="pending" {{ $laporan->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="proses" {{ $laporan->status === 'proses' ? 'selected' : '' }}>Sedang Diproses</option>
                            <option value="selesai" {{ $laporan->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm btn-block">Simpan Perubahan</button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <!-- Kolom Kanan: Thread Percakapan & Form Balas -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 font-weight-bold">
                    <i class="bi bi-chat-dots-fill text-primary mr-1"></i> Percakapan / Thread Tiket
                </h6>
                <span class="badge badge-secondary">{{ $laporan->pesan->count() }} Pesan</span>
            </div>
            <div class="card-body p-4" style="background-color: #f8f9fa;">
                @forelse($laporan->pesan as $msg)
                    @php
                        $isMe = ($msg->sender_user_id === $currentUser->id);
                        $roleClass = match($msg->sender_role) {
                            'admin' => 'badge-danger',
                            'seller' => 'badge-warning',
                            'customer' => 'badge-info',
                            default => 'badge-secondary',
                        };
                        $roleLabel = match($msg->sender_role) {
                            'admin' => 'Admin Support',
                            'seller' => 'Seller',
                            'customer' => 'Customer',
                            default => 'Sistem',
                        };
                    @endphp

                    <div class="mb-4 d-flex {{ $isMe ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="card border {{ $isMe ? 'bg-white shadow-sm' : 'bg-white shadow-sm' }}" style="max-width: 85%; border-radius: 12px;">
                            <div class="card-header py-2 px-3 bg-light d-flex justify-content-between align-items-center" style="border-radius: 12px 12px 0 0;">
                                <div>
                                    <span class="badge {{ $roleClass }} mr-1">{{ $roleLabel }}</span>
                                    <strong class="small text-dark">{{ $msg->sender?->name ?: ucfirst($msg->sender_role) }}</strong>
                                </div>
                                <div class="small text-muted ml-3">
                                    @if($msg->sumber === 'telegram')
                                        <span class="badge badge-light border text-primary" title="Dikirim via Bot Telegram">
                                            <i class="bi bi-telegram"></i> Telegram
                                        </span>
                                    @else
                                        <span class="badge badge-light border text-muted" title="Dikirim via Website">
                                            <i class="bi bi-globe"></i> Web
                                        </span>
                                    @endif
                                    <span class="ml-1">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="card-body py-2 px-3 text-dark" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.5;">{{ $msg->body }}</div>

                            @if($msg->lampiran)
                                <div class="card-footer py-2 px-3 bg-white border-top">
                                    @php
                                        $ext = strtolower(pathinfo($msg->lampiran, PATHINFO_EXTENSION));
                                    @endphp
                                    @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                        <a href="{{ asset('assets/img/laporan/' . $msg->lampiran) }}" target="_blank">
                                            <img src="{{ asset('assets/img/laporan/' . $msg->lampiran) }}" alt="Lampiran" class="rounded border" style="max-height: 160px; max-width: 100%;">
                                        </a>
                                    @else
                                        <a href="{{ asset('assets/img/laporan/' . $msg->lampiran) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-file-earmark-pdf-fill text-danger mr-1"></i> Buka Lampiran Berkas ({{ strtoupper($ext) }})
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat-left-dots display-4 d-block mb-2 text-muted"></i>
                        Belum ada percakapan pada tiket ini.
                    </div>
                @endforelse
            </div>

            <!-- Form Balasan Pesan -->
            <div class="card-footer bg-white p-3 border-top">
                @if($laporan->status === 'selesai')
                    <div class="alert alert-secondary mb-0 small text-center">
                        <i class="bi bi-check-circle-fill text-success mr-1"></i> Tiket ini telah ditandai <b>Selesai</b>. Jika Anda masih memiliki pertanyaan atau kendala lanjutan, Anda tetap dapat mengirimkan pesan balasan di bawah ini.
                    </div>
                @endif

                <form action="{{ route('laporan.pesan.store', $laporan->id) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                    @csrf
                    <div class="form-group mb-2">
                        <label for="pesan" class="small font-weight-bold text-muted mb-1">Tulis Balasan:</label>
                        <textarea name="pesan" id="pesan" rows="3" class="form-control @error('pesan') is-invalid @enderror" placeholder="Ketik pesan balasan Anda di sini..." required>{{ old('pesan') }}</textarea>
                        @error('pesan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-row align-items-center">
                        <div class="col-12 col-md-8 mb-2 mb-md-0">
                            <div class="custom-file custom-file-sm">
                                <input type="file" name="lampiran" class="form-control-file @error('lampiran') is-invalid @enderror" id="lampiran" accept="image/*,.pdf">
                                <small class="text-muted d-block" style="font-size: 0.78rem;">Lampiran opsional: Gambar (JPG/PNG) atau PDF, maks 2MB.</small>
                                @error('lampiran')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-right">
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-send-fill mr-1"></i> Kirim Balasan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

