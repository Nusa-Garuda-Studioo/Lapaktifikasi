@extends('layout')

@section('title', 'Setting Website')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-3">
                <h4 class="mb-0 text-dark"><i class="bi bi-gear-fill text-primary mr-2"></i> Pengaturan Website</h4>
            </div>
            
            <div class="card-body pt-0">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-left-success" role="alert">
                    <strong>Berhasil!</strong> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif
                
                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-left-danger" role="alert">
                    <strong>Gagal!</strong> Periksa kembali inputan Anda.
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif

                <form action="{{ route('admin.setting_website.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <!-- Logo & Favicon & Auth Hero Section -->
                        <div class="col-md-4 mb-4">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="font-weight-bold mb-3 border-bottom pb-2">Logo, Favicon & Gambar Auth</h6>
                                
                                <div class="form-group text-center">
                                    <label class="d-block font-weight-bold text-left">Logo Website</label>
                                    <div class="mb-2 p-3 bg-white border rounded d-inline-block" style="min-width: 150px; min-height: 80px;">
                                        @if($settings->logo_path)
                                            <img src="{{ asset($settings->logo_path) }}" alt="Logo" style="max-width: 100%; max-height: 60px;">
                                        @else
                                            <span class="text-muted d-block mt-3"><i class="bi bi-image"></i> Belum ada logo</span>
                                        @endif
                                    </div>
                                    <input type="file" name="logo" class="form-control-file mt-2" accept="image/*">
                                    <small class="text-muted text-left d-block mt-1">Disarankan: Format PNG transparan.</small>
                                </div>
                                
                                <hr>
                                
                                <div class="form-group text-center">
                                    <label class="d-block font-weight-bold text-left">Favicon (Ikon Tab)</label>
                                    <div class="mb-2 p-3 bg-white border rounded d-inline-block" style="min-width: 80px; min-height: 80px;">
                                        @if($settings->favicon_path)
                                            <img src="{{ asset($settings->favicon_path) }}" alt="Favicon" style="max-width: 32px; max-height: 32px;">
                                        @else
                                            <span class="text-muted d-block mt-2"><i class="bi bi-image"></i></span>
                                        @endif
                                    </div>
                                    <input type="file" name="favicon" class="form-control-file mt-2" accept="image/png, image/jpeg, image/x-icon, image/svg+xml">
                                    <small class="text-muted text-left d-block mt-1">Disarankan: Persegi, resolusi 32x32 atau 64x64.</small>
                                </div>

                                <hr>

                                <div class="form-group text-center">
                                    <label class="d-block font-weight-bold text-left">Gambar Login & Register (Auth Hero)</label>
                                    <div class="mb-2 p-3 bg-white border rounded d-inline-block" style="min-width: 150px; min-height: 100px;">
                                        @if($settings->auth_hero_path)
                                            <img src="{{ asset($settings->auth_hero_path) }}" alt="Auth Hero" style="max-width: 100%; max-height: 100px; object-fit: contain;">
                                        @else
                                            <img src="{{ asset('assets/img/auth_hero.png') }}" alt="Default Auth Hero" style="max-width: 100%; max-height: 100px; object-fit: contain;">
                                            <small class="text-muted d-block mt-1">(Gambar Default)</small>
                                        @endif
                                    </div>
                                    <input type="file" name="auth_hero" class="form-control-file mt-2" accept="image/*">
                                    <small class="text-muted text-left d-block mt-1">Gambar ilustrasi di panel kanan halaman login & register.</small>
                                </div>

                                <hr>

                                <div class="form-group text-center">
                                    <label class="d-block font-weight-bold text-left">Logo Visi Kami (Landing Page)</label>
                                    <div class="mb-2 p-3 bg-white border rounded d-inline-block" style="min-width: 140px; min-height: 120px;">
                                        @if($settings->visi_logo_path)
                                            <img src="{{ asset($settings->visi_logo_path) }}" alt="Logo Visi" style="max-width: 120px; max-height: 100px; object-fit: contain;">
                                        @else
                                            <img src="{{ asset('assets/img/visi_kami.svg') }}" alt="Default Logo Visi" style="max-width: 120px; max-height: 100px; object-fit: contain;">
                                            <small class="text-muted d-block mt-1">(Gambar Default)</small>
                                        @endif
                                    </div>
                                    <input type="file" name="visi_logo" class="form-control-file mt-2" accept="image/*">
                                    <small class="text-muted text-left d-block mt-1">Logo kartu Visi Kami di landing page.</small>
                                </div>

                                <hr>

                                <div class="form-group text-center">
                                    <label class="d-block font-weight-bold text-left">Logo Misi Utama (Landing Page)</label>
                                    <div class="mb-2 p-3 bg-white border rounded d-inline-block" style="min-width: 140px; min-height: 120px;">
                                        @if($settings->misi_logo_path)
                                            <img src="{{ asset($settings->misi_logo_path) }}" alt="Logo Misi" style="max-width: 120px; max-height: 100px; object-fit: contain;">
                                        @else
                                            <img src="{{ asset('assets/img/misi_utama.svg') }}" alt="Default Logo Misi" style="max-width: 120px; max-height: 100px; object-fit: contain;">
                                            <small class="text-muted d-block mt-1">(Gambar Default)</small>
                                        @endif
                                    </div>
                                    <input type="file" name="misi_logo" class="form-control-file mt-2" accept="image/*">
                                    <small class="text-muted text-left d-block mt-1">Logo kartu Misi Utama di landing page.</small>
                                </div>
                            </div>
                        </div>

                        <!-- General Information Section -->
                        <div class="col-md-8 mb-4">
                            <div class="p-4 border rounded">
                                <h6 class="font-weight-bold mb-3 border-bottom pb-2">Informasi Umum</h6>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold">Nama Website / Judul <span class="text-danger">*</span></label>
                                    <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $settings->site_name) }}" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold">Deskripsi Singkat</label>
                                    <textarea name="site_description" class="form-control" rows="3">{{ old('site_description', $settings->site_description) }}</textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Email Kontak</label>
                                            <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $settings->contact_email) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Nomor Telepon / WhatsApp</label>
                                            <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $settings->contact_phone) }}">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold">Alamat Kantor / Operasional</label>
                                    <textarea name="address" class="form-control" rows="2" placeholder="Contoh: Jl. Golf RT06/08, Ciriung, Kec. Cibinong, Kab. Bogor, Jawa Barat 16918">{{ old('address', $settings->address) }}</textarea>
                                    <small class="text-muted">Alamat yang ditampilkan pada kolom lokasi di footer website.</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">Link Embed Google Maps (Footer)</label>
                                    <textarea name="maps_embed_url" class="form-control" rows="2" placeholder="Contoh: https://maps.google.com/maps?q=Bogor+Jawa+Barat&output=embed atau kode <iframe> embed">{{ old('maps_embed_url', $settings->maps_embed_url) }}</textarea>
                                    <small class="text-muted">Masukkan link URL embed Google Maps atau salin kode <code>&lt;iframe src="..."&gt;</code> dari Google Maps. Kosongkan untuk menggunakan peta default lokasi alamat.</small>
                                    @if($settings->maps_embed_url)
                                        <div class="mt-2 p-2 bg-light border rounded">
                                            <small class="text-muted d-block mb-1 font-weight-bold"><i class="bi bi-geo-alt"></i> Pratinjau Peta Saat Ini:</small>
                                            <div style="height: 130px; border-radius: 8px; overflow: hidden;">
                                                <iframe src="{{ $settings->maps_embed_url }}" width="100%" height="130" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                                
                        <!-- Payment Gateways Configuration Section -->
                        <div class="col-12 mb-4">
                            <div class="p-4 border rounded bg-white shadow-sm" style="border-radius: 12px;">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <div>
                                        <h6 class="font-weight-bold mb-1 text-dark">
                                            <i class="bi bi-credit-card-2-front-fill text-primary mr-1"></i> Gateway Pembayaran Aktif
                                        </h6>
                                        <small class="text-muted">Pilih metode gateway pembayaran yang dapat digunakan oleh pelanggan saat melakukan checkout.</small>
                                    </div>
                                    <span class="badge badge-info font-weight-bold px-2 py-1">Minimal 1 Gateway Aktif</span>
                                </div>

                                <div class="row">
                                    <!-- Midtrans -->
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 h-100 {{ $settings->is_midtrans_active ? 'border-primary bg-light' : 'bg-white' }}" style="border-radius: 10px; transition: all 0.2s;">
                                            <div class="custom-control custom-switch mb-2">
                                                <input type="checkbox" class="custom-control-input" id="switchMidtrans" name="is_midtrans_active" value="1" {{ old('is_midtrans_active', $settings->is_midtrans_active) ? 'checked' : '' }}>
                                                <label class="custom-control-label font-weight-bold" for="switchMidtrans" style="cursor: pointer;">
                                                    Midtrans
                                                </label>
                                            </div>
                                            <p class="text-muted small mb-2">
                                                Pop-up Snap: QRIS (GoPay/ShopeePay), Virtual Account (BCA, BNI, BRI, Mandiri), Kartu Kredit/Debit, Minimarket.
                                            </p>
                                            <div>
                                                <span class="badge badge-{{ $settings->is_midtrans_active ? 'success' : 'secondary' }}">
                                                    {{ $settings->is_midtrans_active ? 'AKTIF' : 'NONAKTIF' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TriPay -->
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 h-100 {{ $settings->is_tripay_active ? 'border-primary bg-light' : 'bg-white' }}" style="border-radius: 10px; transition: all 0.2s;">
                                            <div class="custom-control custom-switch mb-2">
                                                <input type="checkbox" class="custom-control-input" id="switchTripay" name="is_tripay_active" value="1" {{ old('is_tripay_active', $settings->is_tripay_active) ? 'checked' : '' }}>
                                                <label class="custom-control-label font-weight-bold" for="switchTripay" style="cursor: pointer;">
                                                    TriPay
                                                </label>
                                            </div>
                                            <p class="text-muted small mb-2">
                                                Multi-Channel: QRIS, Virtual Account (BCA, Mandiri, BNI, BRI, Permata, CIMB), Minimarket (Indomaret/Alfamart), E-Wallet.
                                            </p>
                                            <div>
                                                <span class="badge badge-{{ $settings->is_tripay_active ? 'success' : 'secondary' }}">
                                                    {{ $settings->is_tripay_active ? 'AKTIF' : 'NONAKTIF' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pakasir -->
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 h-100 {{ $settings->is_pakasir_active ? 'border-primary bg-light' : 'bg-white' }}" style="border-radius: 10px; transition: all 0.2s;">
                                            <div class="custom-control custom-switch mb-2">
                                                <input type="checkbox" class="custom-control-input" id="switchPakasir" name="is_pakasir_active" value="1" {{ old('is_pakasir_active', $settings->is_pakasir_active) ? 'checked' : '' }}>
                                                <label class="custom-control-label font-weight-bold" for="switchPakasir" style="cursor: pointer;">
                                                    Pakasir
                                                </label>
                                            </div>
                                            <p class="text-muted small mb-2">
                                                Direct Payment: QRIS otomatis real-time via direct checkout URL Pakasir.
                                            </p>
                                            <div>
                                                <span class="badge badge-{{ $settings->is_pakasir_active ? 'success' : 'secondary' }}">
                                                    {{ $settings->is_pakasir_active ? 'AKTIF' : 'NONAKTIF' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 text-right mt-2">
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold" style="border-radius: 8px;"><i class="bi bi-save mr-1"></i> Simpan Pengaturan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card Broadcast Web Push Notification (PWA) -->
        <div class="card shadow-sm border-0 mt-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-3 d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="mb-0 text-dark"><i class="bi bi-broadcast-pin text-danger mr-2"></i> Broadcast Web Push Notification (PWA)</h4>
                @php
                    $totalSubscribers = \App\Models\PushSubscription::count();
                @endphp
                <div class="d-flex align-items-center mt-2 mt-md-0">
                    <span class="badge badge-pill badge-primary px-3 py-2 font-weight-bold" style="font-size: 12px;">
                        <i class="bi bi-phone mr-1"></i> <span id="badgeSubCount">{{ $totalSubscribers }}</span> Perangkat Terdaftar
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="registerCurrentAdminDevice()" title="Daftarkan browser ini sebagai perangkat penerima notifikasi">
                        <i class="bi bi-bell-fill mr-1"></i> Daftarkan Browser Ini
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info ml-1" onclick="testCurrentAdminDevice()" title="Uji notifikasi langsung ke browser ini">
                        <i class="bi bi-send-check mr-1"></i> Uji ke Browser Ini
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">
                <p class="text-muted small">
                    Kirimkan pemberitahuan instan langsung ke layar HP dan browser laptop customer maupun seller tanpa biaya SMS / WhatsApp Gateway. Notifikasi akan diterima meskipun pengguna sedang tidak membuka website.
                </p>

                <form id="formBroadcastPush">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Judul Notifikasi <span class="text-danger">*</span></label>
                            <input type="text" id="push_title" name="title" class="form-control" placeholder="Contoh: 🔥 Flash Sale Netflix & Canva Pro!" required maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Target Penerima</label>
                            <select id="push_target" name="target" class="form-control">
                                <option value="all">Semua Perangkat Terdaftar (Customer & Seller)</option>
                                <option value="customer">Khusus Customer</option>
                                <option value="seller">Khusus Mitra Seller</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="font-weight-bold">Isi Pesan Notifikasi <span class="text-danger">*</span></label>
                            <textarea id="push_body" name="body" class="form-control" rows="2" placeholder="Tuliskan pesan menarik dan singkat..." required maxlength="255"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="font-weight-bold">URL Tujuan (Ketika Notifikasi Diklik)</label>
                            <input type="url" id="push_url" name="url" class="form-control" value="{{ url('/premium/katalog') }}" placeholder="https://...">
                            <small class="text-muted">Pengguna akan langsung diarahkan ke tautan ini saat mengklik notifikasi.</small>
                        </div>
                        <div class="col-12 text-right">
                            <button type="button" onclick="sendBroadcastPush()" id="btnSendBroadcast" class="btn btn-danger px-4 font-weight-bold" style="border-radius: 8px;">
                                <i class="bi bi-send-fill mr-1"></i> Siarkan Notifikasi Sekarang
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Daftar Perangkat Terdaftar -->
                <div class="mt-4 pt-3 border-top">
                    <h5 class="font-weight-bold text-dark mb-3"><i class="bi bi-phone-vibrate text-primary mr-1"></i> Perangkat Terdaftar Saat Ini</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Perangkat / OS</th>
                                    <th>Penyedia Push</th>
                                    <th>Pemilik Akun</th>
                                    <th>Waktu Terdaftar</th>
                                    <th style="width: 170px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pushSubscriptions ?? [] as $idx => $sub)
                                <tr id="row-sub-{{ $sub->id }}">
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <strong>{{ $sub->device_name }}</strong>
                                        <div class="text-muted small" style="font-size: 11px;">{{ Str::limit($sub->user_agent, 45) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border font-weight-bold px-2 py-1">
                                            {{ $sub->push_provider }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sub->user)
                                            <span class="badge badge-info">{{ $sub->user->name }}</span>
                                        @else
                                            <span class="badge badge-secondary">Tamu / Pengunjung</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span title="{{ $sub->created_at }}">{{ $sub->created_at->diffForHumans() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="testSpecificDevice({{ $sub->id }})" title="Kirim notifikasi uji coba ke perangkat ini">
                                            <i class="bi bi-send-fill mr-1"></i> Uji
                                        </button>
                                        <button type="button" class="btn btn-xs btn-outline-danger ml-1" onclick="deleteSpecificDevice({{ $sub->id }})" title="Hapus perangkat">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle mr-1"></i> Belum ada perangkat yang mengaktifkan izin notifikasi. Kunjungi website atau PWA dari HP/browser lain, lalu klik <strong>"Aktifkan"</strong> pada banner notifikasi.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Integrasi Telegram Admin -->
<div class="row mt-4">
    <div class="col-12">
        <x-telegram-link-card :user="auth()->user()" role="admin" card-id="telegram" />
    </div>
</div>

@push('scripts')
<script>
    function registerCurrentAdminDevice() {
        if (window.LapaktifikasiPWA && window.LapaktifikasiPWA.requestPush) {
            window.LapaktifikasiPWA.requestPush(false).then(success => {
                if (success) {
                    setTimeout(() => location.reload(), 1500);
                }
            });
        } else {
            alert('Fitur PWA belum termuat di halaman ini.');
        }
    }

    function testCurrentAdminDevice() {
        if (window.LapaktifikasiPWA && window.LapaktifikasiPWA.testPush) {
            window.LapaktifikasiPWA.testPush();
        } else {
            alert('Fitur PWA belum termuat di halaman ini.');
        }
    }

    function testSpecificDevice(id) {
        Swal.fire({
            title: 'Kirim Uji Coba Notifikasi?',
            text: 'Notifikasi akan dikirimkan secara khusus ke perangkat ini.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Kirim Sekarang',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                try {
                    const res = await fetch(`/admin/webpush/device/${id}/test`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terkirim!',
                            text: data.message,
                            confirmButtonColor: '#4f46e5'
                        });
                    } else {
                        throw new Error(data.message || 'Gagal mengirim');
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengirim',
                        text: err.message
                    });
                }
            }
        });
    }

    function deleteSpecificDevice(id) {
        Swal.fire({
            title: 'Hapus Perangkat?',
            text: 'Perangkat ini tidak akan lagi menerima siaran notifikasi web push.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`/admin/webpush/subscription/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        const row = document.getElementById(`row-sub-${id}`);
                        if (row) row.remove();
                        Swal.fire({
                            icon: 'success',
                            title: 'Dihapus',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Gagal menghapus');
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: err.message
                    });
                }
            }
        });
    }

    function sendBroadcastPush() {
        const title = document.getElementById('push_title').value.trim();
        const body = document.getElementById('push_body').value.trim();
        const url = document.getElementById('push_url').value.trim();
        const target = document.getElementById('push_target').value;

        if (!title || !body) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: 'Harap isi Judul dan Isi Pesan notifikasi terlebih dahulu.'
            });
            return;
        }

        Swal.fire({
            title: 'Kirim Broadcast Notifikasi?',
            text: 'Notifikasi akan dikirimkan langsung ke seluruh perangkat terdaftar.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Siarkan!',
            cancelButtonText: 'Batal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const btn = document.getElementById('btnSendBroadcast');
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-1"></i> Mengirim Notifikasi...';

                try {
                    const res = await fetch("{{ route('admin.webpush.broadcast') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ title, body, url, target })
                    });

                    const data = await res.json();
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Broadcast Berhasil!',
                            text: data.message,
                            confirmButtonColor: '#4f46e5'
                        });
                        document.getElementById('push_title').value = '';
                        document.getElementById('push_body').value = '';
                    } else {
                        throw new Error(data.message || 'Gagal mengirim broadcast');
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengirim',
                        text: err.message
                    });
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-send-fill mr-1"></i> Siarkan Notifikasi Sekarang';
                }
            }
        });
    }
</script>
@endpush
@endsection
