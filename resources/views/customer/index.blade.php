@extends('layout')

@section('title', 'Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/customer-dashboard.css') }}">
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
                
                <span class="profile-badge">{{ Auth::user()->role_id == 1 ? 'Administrator' : 'Customer' }}</span>
                
                <div class="profile-meta-list">
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">ID Akun</span>
                        <span class="profile-meta-value">#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="profile-meta-item">
                        <span class="profile-meta-label">Bergabung</span>
                        <span class="profile-meta-value">{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</span>
                    </div>
                </div>
                
                <div class="profile-meta-barcode">
                    <div>||| | |||| | || ||| ||</div>
                    <div style="font-size: 0.5rem; margin-top: 4px;">LAPAKTIFIKASI-MEMBER</div>
                </div>
            </div>
            
            <!-- Right Card: Form fields -->
            <div class="mono-card">
                <h4 class="form-title">Pengaturan Profil</h4>
                
                <form method="post" action="{{ url('update_profile') }}">
                    @csrf

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

                </form>
            </div>
        </div>
    </div>

@endsection