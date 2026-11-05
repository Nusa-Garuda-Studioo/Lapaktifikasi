@extends('layout')

@section('title', 'Laporan Masalah')

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

<div class="row">
    <!-- Form Tambah Laporan -->
    <div class="col-12 col-lg-5">
        <!-- Card Integrasi Telegram & Saluran Notifikasi -->
        @php
            $currentUser = Auth::user();
        @endphp
        <div class="card mb-4 border shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 font-weight-bold">
                    <i class="bi bi-telegram text-primary mr-1"></i> Integrasi Telegram
                </h6>
                <div>
                    @if($currentUser->hasTelegram())
                        <span class="badge badge-success px-2 py-1"><i class="bi bi-check-circle-fill"></i> Terhubung</span>
                    @elseif($currentUser->telegram_blocked_at)
                        <span class="badge badge-danger px-2 py-1"><i class="bi bi-exclamation-triangle-fill"></i> Bot Diblokir</span>
                    @else
                        <span class="badge badge-secondary px-2 py-1"><i class="bi bi-dash-circle"></i> Belum Terhubung</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Dapatkan notifikasi instan untuk status laporan dan tiket garansi Anda. <em>(Opsional, bukan syarat membuat laporan)</em>.
                </p>

                @if($currentUser->hasTelegram())
                    <div class="alert alert-light border d-flex justify-content-between align-items-center p-2 mb-3">
                        <small class="text-success font-weight-bold">
                            <i class="bi bi-person-check-fill mr-1"></i>
                            {{ $currentUser->telegram_username ? '@' . $currentUser->telegram_username : 'ID ' . substr((string)$currentUser->telegram_chat_id, 0, 4) . '***' }}
                        </small>
                        <form method="POST" action="{{ route('telegram.unlink') }}" onsubmit="return confirm('Putuskan tautan Telegram Anda?')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-xs py-1 px-2" style="font-size: 0.75rem;">
                                Putuskan
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mb-3 text-center">
                        <button type="button" class="btn btn-outline-primary btn-sm btn-block" onclick="generateLaporanTelegramLink()" id="btn-laporan-telegram">
                            <i class="bi bi-link-45deg"></i> Hubungkan Telegram
                        </button>
                    </div>
                    <div id="laporan-telegram-result" style="display: none;" class="alert alert-info py-2 px-3 small mb-3">
                        <div><strong>Tautan Siap (15 Menit):</strong></div>
                        <a href="#" id="laporan-telegram-open-btn" target="_blank" class="btn btn-primary btn-sm mt-1 mb-1">
                            <i class="bi bi-telegram"></i> Buka Bot Telegram
                        </a>
                    </div>
                @endif

                <!-- Pilihan Saluran Notifikasi -->
                <form method="POST" action="{{ route('telegram.channel_preference') }}">
                    @csrf
                    <label class="small font-weight-bold text-muted mb-1 d-block">Saluran Notifikasi Tiket:</label>
                    <div class="input-group input-group-sm">
                        <select name="notif_channel_laporan" class="custom-select custom-select-sm">
                            <option value="auto" {{ $currentUser->notif_channel_laporan === 'auto' ? 'selected' : '' }}>Otomatis (Telegram / WhatsApp)</option>
                            <option value="telegram" {{ $currentUser->notif_channel_laporan === 'telegram' ? 'selected' : '' }}>Telegram Saja</option>
                            <option value="whatsapp" {{ $currentUser->notif_channel_laporan === 'whatsapp' ? 'selected' : '' }}>WhatsApp Saja</option>
                        </select>
                        <div class="input-group-append">
                            <button class="btn btn-dark btn-sm" type="submit">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Buat Laporan Baru</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('customer.laporan.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @php
                        $userPhone = Auth::user()->customer->first()?->nomor_telepon;
                    @endphp
                    <div class="form-group">
                        <label for="nomor_telepon">Nomor WhatsApp Terdaftar</label>
                        <input type="text" class="form-control {{ empty($userPhone) ? 'is-invalid border-danger' : '' }}" id="nomor_telepon" value="{{ $userPhone ?? 'Belum diisi' }}" readonly disabled>
                        @if(empty($userPhone))
                            <div class="invalid-feedback d-block mt-1">
                                <i class="bi bi-exclamation-circle-fill mr-1"></i>Anda belum mengisi nomor telepon. Silakan <a href="{{ url('profile_customer/' . Auth::user()->id) }}" class="font-weight-bold text-underline">isi di profil Anda</a> terlebih dahulu agar admin bisa menghubungi Anda.
                            </div>
                        @else
                            <small class="form-text text-muted">Admin akan menghubungi Anda via WhatsApp ke nomor ini. Jika salah, silakan update di profil Anda.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="judul">Judul Laporan</label>
                        <input type="text" class="form-control @error('judul') is-invalid @enderror" id="judul" name="judul" value="{{ old('judul') }}" placeholder="Contoh: Akun tidak bisa login" required>
                        @error('judul')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Masalah</label>
                        <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" rows="4" placeholder="Jelaskan detail kendala Anda..." style="height: auto;" required>{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="gambar">Upload Screenshot / Gambar (Opsional)</label>
                        <input type="file" class="form-control-file @error('gambar') is-invalid @enderror" id="gambar" name="gambar" accept="image/*">
                        <small class="form-text text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                        @error('gambar')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary float-right mt-2">Kirim Laporan</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Laporan -->
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h4>Riwayat Laporan Masalah</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table-1">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Gambar</th>
                                <th>Status</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporan as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $item->judul }}</strong></td>
                                <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($item->gambar)
                                    <a href="{{ asset('assets/img/laporan/' . $item->gambar) }}" target="_blank">
                                        <img src="{{ asset('assets/img/laporan/' . $item->gambar) }}" alt="lampiran" class="rounded" style="max-height: 40px; max-width: 40px; object-fit: cover;">
                                    </a>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->status == 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                    @elseif($item->status == 'proses')
                                    <span class="badge badge-primary">Diproses</span>
                                    @else
                                    <span class="badge badge-success">Selesai</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewDetailLaporan({{ json_encode($item) }})">Detail</button>
                                    <a href="{{ route('laporan.show', $item->id) }}" class="btn btn-sm btn-info ml-1" title="Buka Thread Percakapan">
                                        <i class="bi bi-chat-dots-fill"></i> Thread
                                        @if($item->pesan && $item->pesan->count() > 0)
                                            <span class="badge badge-light text-dark ml-1">{{ $item->pesan->count() }}</span>
                                        @endif
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada riwayat laporan masalah.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Laporan -->
<div class="modal fade" id="detailLaporanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-judul">Detail Laporan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">Status Laporan</label>
                    <div id="modal-status-badge"></div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Judul</label>
                    <p id="modal-judul-text" class="text-primary"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Deskripsi Masalah</label>
                    <p id="modal-deskripsi-text" class="border p-2 bg-light rounded text-dark" style="white-space: pre-wrap;"></p>
                </div>
                <div class="form-group" id="modal-gambar-group">
                    <label class="font-weight-bold">Lampiran Gambar</label>
                    <div>
                        <a id="modal-gambar-link" href="#" target="_blank">
                            <img id="modal-gambar-img" src="" alt="lampiran" class="img-fluid rounded border" style="max-height: 250px;">
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewDetailLaporan(laporan) {
        document.getElementById('modal-judul-text').innerText = laporan.judul;
        document.getElementById('modal-deskripsi-text').innerText = laporan.deskripsi;
        
        const badgeContainer = document.getElementById('modal-status-badge');
        badgeContainer.innerHTML = '';
        if (laporan.status === 'pending') {
            badgeContainer.innerHTML = '<span class="badge badge-warning">Pending</span>';
        } else if (laporan.status === 'proses') {
            badgeContainer.innerHTML = '<span class="badge badge-primary">Diproses</span>';
        } else {
            badgeContainer.innerHTML = '<span class="badge badge-success">Selesai</span>';
        }

        const gambarGroup = document.getElementById('modal-gambar-group');
        if (laporan.gambar) {
            gambarGroup.style.display = 'block';
            document.getElementById('modal-gambar-link').href = "{{ asset('assets/img/laporan') }}/" + laporan.gambar;
            document.getElementById('modal-gambar-img').src = "{{ asset('assets/img/laporan') }}/" + laporan.gambar;
        } else {
            gambarGroup.style.display = 'none';
        }

        $('#detailLaporanModal').modal('show');
    }

    function generateLaporanTelegramLink() {
        const btn = document.getElementById('btn-laporan-telegram');
        if (btn) btn.disabled = true;

        fetch("{{ route('telegram.link_token') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            }
        })
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (data.status === 'success') {
                document.getElementById('laporan-telegram-open-btn').href = data.url;
                document.getElementById('laporan-telegram-result').style.display = 'block';
                window.open(data.url, '_blank');
            } else {
                alert(data.message || 'Gagal membuat tautan Telegram.');
            }
        })
        .catch(() => {
            if (btn) btn.disabled = false;
            alert('Koneksi gagal. Coba kembali.');
        });
    }
</script>

@endsection
