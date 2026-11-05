@extends('layout')

@section('title', 'Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/customer-dashboard.css') }}">
    <style>
        .telegram-badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .telegram-badge-connected {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .telegram-badge-blocked {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .telegram-badge-unlinked {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .telegram-channel-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .telegram-channel-card:hover {
            border-color: #000000;
            background-color: #fafafa;
        }
        .telegram-channel-card input[type="radio"]:checked + .channel-info {
            font-weight: 700;
        }
    </style>
@endpush

@section('content')

    @if($success = Session::get('success'))
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            Toast.fire({
                icon: "success",
                title: "{{ $success }}"
            });
        </script>

    @elseif($error = Session::get('error'))
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            Toast.fire({
                icon: "error",
                title: "{{ $error }}"
            });
        </script>

    @elseif($warning = Session::get('warning'))
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            Toast.fire({
                icon: "warning",
                title: "{{ $warning }}"
            });
        </script>
    @endif

    @if($errors->any())
        <script>
            Swal.fire({
                icon: "error",
                title: "Gagal Mengubah Profil",
                html: {!! json_encode(implode('<br>', array_map('e', $errors->all()))) !!}
            });
        </script>
    @endif

    <div class="profile-container">
        <h4 class="profile-header-title">Profil Anda</h4>
        
        <div class="profile-grid">
            <!-- Left Card: Visual profile -->
            <div class="mono-card text-center">
                <div class="profile-avatar-wrapper">
                    <img class="profile-avatar-img" 
                         src="{{ $user->profile_picture ? asset('assets/img/avatar/' . $user->profile_picture) : asset('assets/img/avatar/' . session('profile_picture')) }}" 
                         alt="avatar">
                </div>
                
                <h3 class="profile-user-name">{{ (Auth::user()->name == '' ? 'User Baru' : $user->name) }}</h3>
                <p class="profile-user-email">{{ $user->email }}</p>
                
                <span class="profile-badge">
                    @if(Auth::user()->role_id == 1)
                        Administrator
                    @elseif(Auth::user()->role_id == 3)
                        Mitra Seller
                    @else
                        Customer
                    @endif
                </span>
                
                <div class="profile-meta-list">
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">ID Akun</span>
                        <span class="profile-meta-value">#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">Bergabung</span>
                        <span class="profile-meta-value">{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">Telegram</span>
                        <span class="profile-meta-value">
                            @if($user->hasTelegram())
                                <span class="text-success font-weight-bold"><i class="bi bi-check-circle-fill"></i> Terhubung</span>
                            @elseif($user->telegram_blocked_at)
                                <span class="text-danger font-weight-bold"><i class="bi bi-x-circle-fill"></i> Diblokir</span>
                            @else
                                <span class="text-muted">Belum Taut</span>
                            @endif
                        </span>
                    </div>
                </div>
                
                <div class="profile-meta-barcode">
                    <div>||| | |||| | || ||| ||</div>
                    <div style="font-size: 0.5rem; margin-top: 4px;">LAPAKTIFIKASI-MEMBER</div>
                </div>
            </div>
            
            <!-- Right Column: Settings + Telegram -->
            <div class="d-flex flex-column gap-4" style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Card 1: Pengaturan Profil -->
                <div class="mono-card">
                    <h4 class="form-title">Pengaturan Profil</h4>
                    
                    <form method="post" action="{{ url('update_profile') }}">
                        @csrf

                        @if($user->customer && $user->customer->count() > 0)
                            @foreach($user->customer as $item)
                                <!-- Name Field -->
                                <div class="mono-form-group">
                                    <label class="mono-form-label">Nama Lengkap</label>
                                    <div class="mono-input-wrapper">
                                        <input type="text" class="mono-input" name="name" autocomplete="off"
                                            placeholder="Nama Lengkap Anda" value="{{ (Auth::user()->name == '' ? '' : $user->name) }}">
                                        <i class="bi bi-person-fill mono-input-icon"></i>
                                    </div>
                                    @if($user->name == '')
                                        <div class="mono-warning">
                                            <i class="bi bi-exclamation-circle-fill"></i>
                                            <span>Isi nama lengkap Anda (Wajib).</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Email Field (Readonly) -->
                                <div class="mono-form-group">
                                    <label class="mono-form-label">Alamat Email</label>
                                    <div class="mono-input-wrapper">
                                        <input type="email" class="mono-input" placeholder="Email" name="email" autocomplete="off"
                                            value="{{ $user->email }}" readonly>
                                        <i class="bi bi-envelope-at-fill mono-input-icon"></i>
                                    </div>
                                </div>

                                <!-- Phone Number Field -->
                                <div class="mono-form-group">
                                    <label class="mono-form-label">Nomor Telepon</label>
                                    <div class="mono-input-wrapper">
                                        <input type="text" class="mono-input" placeholder="Nomor Telepon Anda" name="nomor_telepon"
                                            autocomplete="off"
                                            value="{{ ($item->nomor_telepon == '' ? '' : $item->nomor_telepon) }}">
                                        <i class="bi bi-telephone-fill mono-input-icon"></i>
                                    </div>
                                    @if($item->nomor_telepon == '')
                                        <div class="mono-warning">
                                            <i class="bi bi-exclamation-circle-fill"></i>
                                            <span>Isi nomor telepon agar admin bisa menghubungi Anda dengan mudah jika terjadi kendala pembayaran (Wajib).</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Submit Button -->
                                <div class="btn-wrapper">
                                    <button type="submit" name="simpan" class="mono-btn-primary">Simpan Perubahan</button>
                                </div>
                            @endforeach
                        @else
                            <!-- Fallback for Admin or users without customer record -->
                            <div class="mono-form-group">
                                <label class="mono-form-label">Nama Lengkap</label>
                                <div class="mono-input-wrapper">
                                    <input type="text" class="mono-input" name="name" autocomplete="off"
                                        placeholder="Nama Lengkap Anda" value="{{ $user->name }}">
                                    <i class="bi bi-person-fill mono-input-icon"></i>
                                </div>
                            </div>

                            <div class="mono-form-group">
                                <label class="mono-form-label">Alamat Email</label>
                                <div class="mono-input-wrapper">
                                    <input type="email" class="mono-input" placeholder="Email" name="email" value="{{ $user->email }}" readonly>
                                    <i class="bi bi-envelope-at-fill mono-input-icon"></i>
                                </div>
                            </div>

                            <div class="btn-wrapper">
                                <button type="submit" name="simpan" class="mono-btn-primary">Simpan Perubahan</button>
                            </div>
                        @endif
                    </form>
                </div>

                <!-- Card 2: Hubungkan Telegram -->
                <div class="mono-card" id="telegram-section">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <h4 class="form-title mb-1" style="margin-bottom: 4px;">
                                <i class="bi bi-telegram text-primary mr-1" style="color: #0088cc;"></i> Hubungkan Telegram
                            </h4>
                            <p class="text-muted text-sm mb-0" style="font-size: 0.875rem; color: #6b7280; margin: 0;">
                                Dapatkan notifikasi instan untuk status laporan dan tiket garansi Anda.
                            </p>
                        </div>
                        <div>
                            @if($user->hasTelegram())
                                <span class="telegram-badge-status telegram-badge-connected">
                                    <i class="bi bi-check-circle-fill"></i> Terhubung
                                </span>
                            @elseif($user->telegram_blocked_at)
                                <span class="telegram-badge-status telegram-badge-blocked">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Bot Diblokir
                                </span>
                            @else
                                <span class="telegram-badge-status telegram-badge-unlinked">
                                    <i class="bi bi-dash-circle"></i> Belum Terhubung
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($user->hasTelegram())
                        <!-- Terhubung State -->
                        <div class="p-3 mb-4 rounded-3" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2" style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div class="font-weight-bold text-success" style="font-weight: 700; color: #166534;">
                                        <i class="bi bi-person-badge mr-1"></i> Akun Telegram: 
                                        <span>{{ $user->telegram_username ? '@' . $user->telegram_username : 'ID ' . substr((string)$user->telegram_chat_id, 0, 4) . '***' }}</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.8rem; color: #4b5563;">
                                        Tertaut sejak: {{ $user->telegram_linked_at ? $user->telegram_linked_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                    </small>
                                </div>
                                <form method="POST" action="{{ route('telegram.unlink') }}" id="form-unlink-telegram" onsubmit="return confirmUnlink(event)">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border: 1px solid #dc2626; color: #dc2626; padding: 6px 14px; border-radius: 8px; font-weight: 600; background: transparent; cursor: pointer;">
                                        <i class="bi bi-link-45deg"></i> Putuskan Tautan
                                    </button>
                                </form>
                            </div>
                        </div>
                    @elseif($user->telegram_blocked_at)
                        <!-- Blocked State -->
                        <div class="p-3 mb-4 rounded-3" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                            <div class="font-weight-bold text-danger mb-1" style="font-weight: 700; color: #991b1b;">
                                <i class="bi bi-exclamation-triangle-fill mr-1"></i> Bot Telegram Terdeteksi Diblokir
                            </div>
                            <p class="text-sm text-danger mb-3" style="font-size: 0.875rem; color: #b91c1c;">
                                Anda memblokir bot Telegram Lapaktifikasi pada {{ $user->telegram_blocked_at->translatedFormat('d M Y, H:i') }} WIB. Silakan buka blokir (unblock) bot kami di aplikasi Telegram Anda agar bot dapat mengirimkan pesan kembali.
                            </p>
                            <div class="d-flex gap-2 flex-wrap" style="display: flex; gap: 8px;">
                                <button type="button" class="btn btn-sm btn-primary" onclick="generateTelegramLink()" style="background: #000; color: #fff; border: 1px solid #000; border-radius: 8px; padding: 6px 14px; font-weight: 600; cursor: pointer;">
                                    Hubungkan Ulang
                                </button>
                                <form method="POST" action="{{ route('telegram.unlink') }}" onsubmit="return confirmUnlink(event)">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="border: 1px solid #d1d5db; color: #4b5563; padding: 6px 14px; border-radius: 8px; font-weight: 600; background: transparent; cursor: pointer;">
                                        Hapus Tautan
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <!-- Unlinked State -->
                        <div class="p-3 mb-4 rounded-3 text-center" style="background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                            <div class="mb-2">
                                <i class="bi bi-telegram" style="font-size: 2.2rem; color: #0088cc;"></i>
                            </div>
                            <h5 style="font-weight: 700; margin-bottom: 4px; font-size: 1rem;">Tautkan Akun Anda ke Telegram Bot</h5>
                            <p class="text-muted text-sm mb-3" style="font-size: 0.85rem; color: #6b7280; max-width: 480px; margin: 0 auto 16px auto;">
                                Dapatkan notifikasi tiket garansi real-time, balasan pesan instan, dan perlindungan akun langsung di genggaman Anda.
                            </p>
                            <button type="button" class="mono-btn-primary" onclick="generateTelegramLink()" id="btn-generate-telegram">
                                <i class="bi bi-link-45deg mr-1"></i> Buat Tautan Telegram
                            </button>
                        </div>
                    @endif

                    <!-- Area Hasil Token (Muncul via AJAX) -->
                    <div id="telegram-link-result" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
                        <h6 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">
                            <i class="bi bi-clock mr-1 text-primary"></i> Tautan Penautan Siap (Berlaku 15 Menit)
                        </h6>
                        <p class="text-sm text-muted mb-3" style="font-size: 0.85rem; color: #64748b;">
                            Klik tombol di bawah ini untuk membuka bot di Telegram dan klik tombol <b>START</b> pada bot:
                        </p>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-3" style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
                            <a href="#" id="telegram-open-btn" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="background-color: #0088cc; border-color: #0088cc; color: #fff; padding: 8px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="bi bi-telegram"></i> Buka Bot Telegram
                            </a>
                            <button type="button" class="btn btn-outline-secondary" onclick="copyTelegramUrl()" style="border: 1px solid #94a3b8; background: #fff; color: #334155; padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                <i class="bi bi-clipboard"></i> Salin URL
                            </button>
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.75rem; color: #94a3b8;">
                            Token: <code id="telegram-token-display" style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px;"></code> (hanya ditampilkan sekali)
                        </small>
                    </div>

                    <!-- Card Preferensi Saluran Notifikasi Laporan -->
                    <div class="mt-4 pt-3 border-top" style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 20px;">
                        <h5 style="font-weight: 700; font-size: 0.95rem; margin-bottom: 12px;">
                            <i class="bi bi-bell mr-1"></i> Preferensi Saluran Notifikasi Tiket Laporan
                        </h5>
                        <form method="POST" action="{{ route('telegram.channel_preference') }}">
                            @csrf
                            <div class="d-flex flex-column gap-2" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                                <label class="telegram-channel-card">
                                    <input type="radio" name="notif_channel_laporan" value="auto" {{ $user->notif_channel_laporan === 'auto' ? 'checked' : '' }}>
                                    <div class="channel-info">
                                        <div style="font-size: 0.9rem;">Otomatis (Rekomendasi)</div>
                                        <div class="text-muted" style="font-size: 0.8rem; color: #6b7280;">Kirim via Telegram jika tertaut; fallback ke WhatsApp jika belum tertaut.</div>
                                    </div>
                                </label>

                                <label class="telegram-channel-card">
                                    <input type="radio" name="notif_channel_laporan" value="telegram" {{ $user->notif_channel_laporan === 'telegram' ? 'checked' : '' }}>
                                    <div class="channel-info">
                                        <div style="font-size: 0.9rem;">Telegram Saja</div>
                                        <div class="text-muted" style="font-size: 0.8rem; color: #6b7280;">Hanya kirimkan notifikasi ke chat Telegram yang tertaut.</div>
                                    </div>
                                </label>

                                <label class="telegram-channel-card">
                                    <input type="radio" name="notif_channel_laporan" value="whatsapp" {{ $user->notif_channel_laporan === 'whatsapp' ? 'checked' : '' }}>
                                    <div class="channel-info">
                                        <div style="font-size: 0.9rem;">WhatsApp Saja</div>
                                        <div class="text-muted" style="font-size: 0.8rem; color: #6b7280;">Hanya kirimkan update notifikasi melalui nomor WhatsApp profil.</div>
                                    </div>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-sm btn-dark" style="background: #000; color: #fff; border: 1px solid #000; border-radius: 8px; padding: 7px 18px; font-weight: 600; cursor: pointer;">
                                Simpan Preferensi Saluran
                            </button>
                        </form>
                    </div>

                    @if($user->isAdmin())
                        <!-- Admin Group ID Information -->
                        <div class="mt-4 pt-3 border-top" style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 20px;">
                            <h5 style="font-weight: 700; font-size: 0.95rem; margin-bottom: 8px;">
                                <i class="bi bi-shield-lock mr-1 text-primary"></i> Pengaturan Grup Telegram Admin (Super Admin)
                            </h5>
                            <div class="p-3 rounded-2" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px;">
                                <div class="text-sm mb-1" style="font-size: 0.85rem; color: #334155;">
                                    <b>TELEGRAM_ADMIN_GROUP_ID (dari .env):</b>
                                    <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: bold;">
                                        {{ config('services.telegram.admin_group_id') ?: '(Belum dikonfigurasi di file .env)' }}
                                    </code>
                                </div>
                                <div class="text-muted" style="font-size: 0.8rem; color: #64748b;">
                                    Tiket garansi baru dan eskalasi akan otomatis diteruskan ke forum topik grup ini.
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentTelegramUrl = '';

        function generateTelegramLink() {
            const btn = document.getElementById('btn-generate-telegram');
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
                    currentTelegramUrl = data.url;
                    document.getElementById('telegram-open-btn').href = data.url;
                    document.getElementById('telegram-token-display').innerText = data.token;
                    document.getElementById('telegram-link-result').style.display = 'block';

                    Swal.fire({
                        icon: 'success',
                        title: 'Tautan Telegram Dibuat!',
                        text: 'Silakan klik tombol Buka Bot Telegram untuk menyelesaikan penautan (berlaku 15 menit).',
                        confirmButtonText: 'Buka Telegram Sekarang',
                        showCancelButton: true,
                        cancelButtonText: 'Nanti'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(data.url, '_blank');
                        }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal membuat tautan Telegram.' });
                }
            })
            .catch(err => {
                if (btn) btn.disabled = false;
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: 'Koneksi gagal. Silakan coba kembali.' });
            });
        }

        function copyTelegramUrl() {
            if (!currentTelegramUrl) return;
            navigator.clipboard.writeText(currentTelegramUrl).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'URL tautan Telegram disalin ke clipboard!',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        }

        function confirmUnlink(event) {
            event.preventDefault();
            const form = event.target;
            Swal.fire({
                title: 'Putuskan Tautan Telegram?',
                text: 'Anda tidak akan menerima notifikasi tiket garansi melalui bot Telegram lagi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Putuskan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }
    </script>
    @endpush

@endsection
