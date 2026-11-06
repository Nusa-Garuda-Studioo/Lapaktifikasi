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
                                        <input type="text" class="mono-input @error('name') is-invalid @enderror" name="name" autocomplete="off"
                                            placeholder="Nama Lengkap Anda" value="{{ old('name', (Auth::user()->name == '' ? '' : $user->name)) }}">
                                        <i class="bi bi-person-fill mono-input-icon"></i>
                                    </div>
                                    @error('name')
                                        <div class="text-danger small mt-1">
                                            <i class="bi bi-exclamation-circle-fill mr-1"></i> {{ $message }}
                                        </div>
                                    @enderror
                                    @if($user->name == '' && !$errors->has('name'))
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
                                        <input type="text" class="mono-input @error('nomor_telepon') is-invalid @enderror" placeholder="Nomor Telepon Anda" name="nomor_telepon"
                                            autocomplete="off"
                                            value="{{ old('nomor_telepon', ($item->nomor_telepon == '' ? '' : $item->nomor_telepon)) }}">
                                        <i class="bi bi-telephone-fill mono-input-icon"></i>
                                    </div>
                                    @error('nomor_telepon')
                                        <div class="text-danger small mt-1">
                                            <i class="bi bi-exclamation-circle-fill mr-1"></i> {{ $message }}
                                        </div>
                                    @enderror
                                    @if($item->nomor_telepon == '' && !$errors->has('nomor_telepon'))
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

                <!-- Card 2: Hubungkan Telegram Customer -->
                @if(!$user->isSeller() && !$user->isAdmin())
                    <x-telegram-link-card 
                        :user="$user" 
                        role="customer" 
                        card-id="telegram-section" 
                        wrapper-class="mono-card" 
                        :show-channel-preference="true" 
                    />
                @endif
            </div>
        </div>
    </div>

@endsection
