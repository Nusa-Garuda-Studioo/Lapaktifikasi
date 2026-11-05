@props([
    'user' => null,
    'role' => null,
    'cardId' => 'telegram',
    'wrapperClass' => null,
    'showChannelPreference' => null,
    'showAdminGroupInfo' => null,
    'linkTokenRoute' => null,
    'unlinkRoute' => null,
    'channelPreferenceRoute' => null,
])

@php
    $user = $user ?? auth()->user();
    
    // Auto-detect role if not explicitly passed
    if (!$role && $user) {
        if ($user->isSeller()) {
            $role = 'seller';
        } elseif ($user->isAdmin()) {
            $role = 'admin';
        } else {
            $role = 'customer';
        }
    }
    $role = $role ?? 'customer';

    $showChannelPreference = $showChannelPreference ?? ($role === 'customer');
    $showAdminGroupInfo = $showAdminGroupInfo ?? ($role === 'admin' || ($user && $user->isAdmin()));

    $botUsername = config('services.telegram.bot_username', 'LapaktifikasiBot');
    $adminGroupId = config('services.telegram.admin_group_id');
    $slugId = \Illuminate\Support\Str::slug($cardId, '_');

    // Default routes per role with fallback
    if (!$linkTokenRoute) {
        $linkTokenRoute = match($role) {
            'seller' => route('seller.telegram.link_token'),
            'admin' => route('admin.telegram.link_token'),
            'customer' => route('customer.telegram.link_token'),
            default => route('telegram.link_token'),
        };
    }

    if (!$unlinkRoute) {
        $unlinkRoute = match($role) {
            'seller' => route('seller.telegram.unlink'),
            'admin' => route('admin.telegram.unlink'),
            'customer' => route('customer.telegram.unlink'),
            default => route('telegram.unlink'),
        };
    }

    if (!$channelPreferenceRoute) {
        $channelPreferenceRoute = $role === 'customer' 
            ? route('customer.telegram.channel_preference') 
            : route('telegram.channel_preference');
    }

    $title = match($role) {
        'seller' => 'Hubungkan Akun Telegram Seller',
        'admin' => 'Integrasi Akun Telegram Admin',
        default => 'Hubungkan Telegram',
    };

    $subtitle = match($role) {
        'seller' => 'Wajib ditautkan untuk menerima notifikasi tiket kendala garansi pembeli dan tombol aksi cepat (Ambil, Selesai, dsb).',
        'admin' => 'Dapatkan pemberitahuan tiket baru, SLA peringatan, eskalasi otomatis, dan respon langsung ke forum topik grup.',
        default => 'Dapatkan notifikasi instan untuk status laporan dan tiket garansi Anda.',
    };
@endphp

<div id="{{ $cardId }}" class="{{ $wrapperClass ?? 'card shadow-sm border-0' }} {{ $attributes->get('class') }}" style="border-radius: 16px; background: #ffffff; {{ $wrapperClass ? '' : 'padding: 24px; margin-bottom: 24px;' }}">
    <!-- Card Header / Title Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1" style="display: flex; align-items: center; gap: 8px;">
                <h4 class="form-title mb-0" style="margin-bottom: 0; font-size: 1.15rem; font-weight: 700; color: #111827;">
                    <i class="bi bi-telegram mr-1" style="color: #0088cc;"></i> {{ $title }}
                </h4>
                @if($botUsername)
                    <span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.75rem; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569;">
                        <i class="bi bi-robot mr-1 text-primary"></i> Bot: <a href="https://t.me/{{ $botUsername }}" target="_blank" rel="noopener noreferrer" style="color: #0088cc; font-weight: 600; text-decoration: none;">{{ '@' . $botUsername }}</a>
                    </span>
                @endif
            </div>
            <p class="text-muted text-sm mb-0" style="font-size: 0.875rem; color: #6b7280; margin: 0;">
                {{ $subtitle }}
            </p>
        </div>
        <div>
            @if($user && $user->hasTelegram())
                <span class="telegram-badge-status telegram-badge-connected">
                    <i class="bi bi-check-circle-fill"></i> Terhubung
                </span>
            @elseif($user && $user->telegram_blocked_at)
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

    @if($user && $user->hasTelegram())
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
                <form method="POST" action="{{ $unlinkRoute }}" id="form-unlink-{{ $slugId }}" onsubmit="return confirmTelegramUnlink_{{ $slugId }}(event)">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border: 1px solid #dc2626; color: #dc2626; padding: 6px 14px; border-radius: 8px; font-weight: 600; background: transparent; cursor: pointer;">
                        <i class="bi bi-link-45deg"></i> Putuskan Tautan
                    </button>
                </form>
            </div>
        </div>
    @elseif($user && $user->telegram_blocked_at)
        <!-- Blocked State -->
        <div class="p-3 mb-4 rounded-3" style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <div class="font-weight-bold text-danger mb-1" style="font-weight: 700; color: #991b1b;">
                <i class="bi bi-exclamation-triangle-fill mr-1"></i> Bot Telegram Terdeteksi Diblokir
            </div>
            <p class="text-sm text-danger mb-3" style="font-size: 0.875rem; color: #b91c1c;">
                Anda memblokir bot Telegram Lapaktifikasi pada {{ $user->telegram_blocked_at->translatedFormat('d M Y, H:i') }} WIB. Silakan buka blokir (unblock) bot kami di aplikasi Telegram Anda agar bot dapat mengirimkan pesan dan notifikasi tiket kembali.
            </p>
            <div class="d-flex gap-2 flex-wrap" style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-sm btn-primary" onclick="generateTelegramLink_{{ $slugId }}()" style="background: #000; color: #fff; border: 1px solid #000; border-radius: 8px; padding: 6px 14px; font-weight: 600; cursor: pointer;">
                    Hubungkan Ulang
                </button>
                <form method="POST" action="{{ $unlinkRoute }}" onsubmit="return confirmTelegramUnlink_{{ $slugId }}(event)">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="border: 1px solid #d1d5db; color: #4b5563; padding: 6px 14px; border-radius: 8px; font-weight: 600; background: transparent; cursor: pointer;">
                        Hapus Tautan
                    </button>
                </form>
            </div>
        </div>
    @else
        <!-- Unlinked State -->
        <div class="p-4 mb-4 rounded-3 text-center" style="background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 12px; padding: 24px; margin-bottom: 20px;">
            <div class="mb-2">
                <i class="bi bi-telegram" style="font-size: 2.4rem; color: #0088cc;"></i>
            </div>
            @if($role === 'seller')
                <h5 style="font-weight: 700; margin-bottom: 6px; font-size: 1.05rem; color: #111827;">Tautkan Akun Seller Anda ke Bot Telegram</h5>
                <p class="text-muted text-sm mb-3" style="font-size: 0.875rem; color: #6b7280; max-width: 520px; margin: 0 auto 16px auto;">
                    Dapatkan peringatan SLA tiket garansi secara real-time, instruksi eskalasi otomatis, dan respon cepat via tombol Telegram (Ambil Laporan, Minta Info, Selesai).
                </p>
            @elseif($role === 'admin')
                <h5 style="font-weight: 700; margin-bottom: 6px; font-size: 1.05rem; color: #111827;">Tautkan Akun Admin Anda ke Bot Telegram</h5>
                <p class="text-muted text-sm mb-3" style="font-size: 0.875rem; color: #6b7280; max-width: 520px; margin: 0 auto 16px auto;">
                    Pantau tiket garansi masuk, terima eskalasi tiket pelanggan, dan kelola tindakan darurat langsung dari Telegram.
                </p>
            @else
                <h5 style="font-weight: 700; margin-bottom: 6px; font-size: 1.05rem; color: #111827;">Tautkan Akun Anda ke Telegram Bot</h5>
                <p class="text-muted text-sm mb-3" style="font-size: 0.875rem; color: #6b7280; max-width: 520px; margin: 0 auto 16px auto;">
                    Dapatkan notifikasi tiket garansi real-time, balasan pesan instan, dan perlindungan akun langsung di genggaman Anda.
                </p>
            @endif
            <button type="button" class="btn btn-primary" onclick="generateTelegramLink_{{ $slugId }}()" id="btn-generate-{{ $slugId }}" style="background-color: #0088cc; border: 1px solid #0088cc; color: #fff; font-weight: 700; border-radius: 8px; padding: 8px 22px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-link-45deg"></i> Buat Tautan Telegram
            </button>
        </div>
    @endif

    <!-- Area Hasil Token (Muncul via AJAX) -->
    <div id="telegram-link-result-{{ $slugId }}" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
        <h6 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">
            <i class="bi bi-clock mr-1 text-primary"></i> Tautan Penautan Siap (Berlaku 15 Menit)
        </h6>
        <p class="text-sm text-muted mb-3" style="font-size: 0.85rem; color: #64748b;">
            Klik tombol di bawah ini untuk membuka bot di aplikasi Telegram dan tekan tombol <b>START</b> pada bot:
        </p>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3" style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
            <a href="#" id="telegram-open-btn-{{ $slugId }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="background-color: #0088cc; border-color: #0088cc; color: #fff; padding: 8px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-telegram"></i> Buka Bot Telegram
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="copyTelegramUrl_{{ $slugId }}()" style="border: 1px solid #94a3b8; background: #fff; color: #334155; padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i class="bi bi-clipboard"></i> Salin URL
            </button>
        </div>
        <small class="text-muted d-block" style="font-size: 0.75rem; color: #94a3b8;">
            Token: <code id="telegram-token-display-{{ $slugId }}" style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 700; color: #0f172a;"></code> (hanya ditampilkan sekali)
        </small>
    </div>

    @if($showChannelPreference && $user)
        <!-- Preferensi Saluran Notifikasi Laporan (Khusus Customer) -->
        <div class="mt-4 pt-3 border-top" style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 20px;">
            <h5 style="font-weight: 700; font-size: 0.95rem; margin-bottom: 12px; color: #111827;">
                <i class="bi bi-bell mr-1"></i> Preferensi Saluran Notifikasi Tiket Laporan
            </h5>
            <form method="POST" action="{{ $channelPreferenceRoute }}">
                @csrf
                <div class="d-flex flex-column gap-2" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                    <label class="telegram-channel-card">
                        <input type="radio" name="notif_channel_laporan" value="auto" {{ $user->notif_channel_laporan === 'auto' ? 'checked' : '' }}>
                        <div class="channel-info">
                            <div style="font-size: 0.9rem; color: #111827;">Otomatis (Rekomendasi)</div>
                            <div class="text-muted" style="font-size: 0.8rem; color: #6b7280;">Kirim via Telegram jika tertaut; fallback ke WhatsApp jika belum tertaut.</div>
                        </div>
                    </label>

                    <label class="telegram-channel-card">
                        <input type="radio" name="notif_channel_laporan" value="telegram" {{ $user->notif_channel_laporan === 'telegram' ? 'checked' : '' }}>
                        <div class="channel-info">
                            <div style="font-size: 0.9rem; color: #111827;">Telegram Saja</div>
                            <div class="text-muted" style="font-size: 0.8rem; color: #6b7280;">Hanya kirimkan notifikasi ke chat Telegram yang tertaut.</div>
                        </div>
                    </label>

                    <label class="telegram-channel-card">
                        <input type="radio" name="notif_channel_laporan" value="whatsapp" {{ $user->notif_channel_laporan === 'whatsapp' ? 'checked' : '' }}>
                        <div class="channel-info">
                            <div style="font-size: 0.9rem; color: #111827;">WhatsApp Saja</div>
                            <div class="text-muted" style="font-size: 0.8rem; color: #6b7280;">Hanya kirimkan update notifikasi melalui nomor WhatsApp profil.</div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn btn-sm btn-dark" style="background: #000; color: #fff; border: 1px solid #000; border-radius: 8px; padding: 7px 18px; font-weight: 600; cursor: pointer;">
                    Simpan Preferensi Saluran
                </button>
            </form>
        </div>
    @endif

    @if($showAdminGroupInfo)
        <!-- Admin Group ID Information -->
        <div class="mt-4 pt-3 border-top" style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2" style="display: flex; justify-content: space-between; align-items: center;">
                <h5 style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0; color: #111827;">
                    <i class="bi bi-shield-lock mr-1 text-primary"></i> Pengaturan Forum Grup Telegram Admin
                </h5>
                @if($adminGroupId)
                    <span class="badge badge-success px-2 py-1" style="font-size: 0.75rem; background-color: #10b981; color: #fff; border-radius: 6px;">
                        <i class="bi bi-check-circle mr-1"></i> Terkonfigurasi
                    </span>
                @else
                    <span class="badge badge-warning px-2 py-1" style="font-size: 0.75rem; background-color: #f59e0b; color: #fff; border-radius: 6px;">
                        <i class="bi bi-exclamation-circle mr-1"></i> Belum Dikonfigurasi
                    </span>
                @endif
            </div>
            <div class="p-3 rounded-2" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">
                <div class="text-sm mb-1" style="font-size: 0.85rem; color: #334155;">
                    <b>TELEGRAM_ADMIN_GROUP_ID (dari .env):</b>
                    <code style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: #0f172a;">
                        {{ $adminGroupId ?: '(Belum dikonfigurasi di file .env)' }}
                    </code>
                </div>
                <div class="text-muted" style="font-size: 0.8rem; color: #64748b;">
                    Tiket garansi baru, pengingat SLA, dan eskalasi otomatis diteruskan ke forum topik grup ini untuk penanganan bersama tim admin.
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Embedded Scoped Styling & Script -->
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
        background: #fff;
    }
    .telegram-channel-card:hover {
        border-color: #000000;
        background-color: #fafafa;
    }
    .telegram-channel-card input[type="radio"]:checked + .channel-info {
        font-weight: 700;
    }
</style>

<script>
    (function() {
        let currentUrl_{{ $slugId }} = '';

        window.generateTelegramLink_{{ $slugId }} = function() {
            const btn = document.getElementById('btn-generate-{{ $slugId }}');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i> Membuat Tautan...';
            }

            fetch("{{ $linkTokenRoute }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                }
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-link-45deg mr-1"></i> Buat Tautan Telegram';
                }
                if (data.status === 'success') {
                    currentUrl_{{ $slugId }} = data.url;
                    const openBtn = document.getElementById('telegram-open-btn-{{ $slugId }}');
                    if (openBtn) openBtn.href = data.url;
                    const tokenDisp = document.getElementById('telegram-token-display-{{ $slugId }}');
                    if (tokenDisp) tokenDisp.innerText = data.token;
                    const resBox = document.getElementById('telegram-link-result-{{ $slugId }}');
                    if (resBox) resBox.style.display = 'block';

                    if (typeof Swal !== 'undefined') {
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
                        window.open(data.url, '_blank');
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal membuat tautan Telegram.' });
                    } else {
                        alert(data.message || 'Gagal membuat tautan Telegram.');
                    }
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-link-45deg mr-1"></i> Buat Tautan Telegram';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: 'Koneksi gagal. Silakan coba kembali.' });
                } else {
                    alert('Koneksi gagal. Silakan coba kembali.');
                }
            });
        };

        window.copyTelegramUrl_{{ $slugId }} = function() {
            if (!currentUrl_{{ $slugId }}) return;
            navigator.clipboard.writeText(currentUrl_{{ $slugId }}).then(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'URL tautan Telegram disalin ke clipboard!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    alert('URL tautan Telegram disalin!');
                }
            });
        };

        window.confirmTelegramUnlink_{{ $slugId }} = function(event) {
            event.preventDefault();
            const form = event.target;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Putuskan Tautan Telegram?',
                    text: 'Anda tidak akan menerima notifikasi tiket dan laporan melalui bot Telegram lagi.',
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
            } else {
                if (confirm('Putuskan tautan Telegram? Anda tidak akan menerima notifikasi melalui bot lagi.')) {
                    form.submit();
                }
            }
            return false;
        };
    })();
</script>

