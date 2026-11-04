@extends('layout')

@section('title', 'Ganti Password')

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
@endif

    <div class="profile-container">
        <h4 class="profile-header-title">Ganti Password</h4>
        
        <div class="row">
            <div class="col-12">
                <div class="mono-card">
                    <h4 class="form-title">Ubah Kredensial Keamanan</h4>
                    
                    <form method="post" action="{{ url('proses_ganti_password') }}">
                        @csrf

                        <!-- Password Lama -->
                        <div class="mono-form-group">
                            <label class="mono-form-label" for="password_lama">Password Saat Ini (Lama)</label>
                            <div class="mono-input-wrapper">
                                <input type="password" class="mono-input" id="password_lama" name="password_lama"
                                    autocomplete="current-password" required>
                                <i class="bi bi-key-fill mono-input-icon"></i>
                            </div>
                            @if ($errors->has('password_lama'))
                                <div class="mono-warning">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                    <span>{{ ucfirst($errors->first('password_lama')) }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Password Baru -->
                        <div class="mono-form-group">
                            <label class="mono-form-label" for="password_baru">Password Baru</label>
                            <div class="mono-input-wrapper">
                                <input type="password" class="mono-input" id="password_baru" name="password_baru"
                                    autocomplete="new-password" value="{{ old('password_baru') }}" required>
                                <i class="bi bi-shield-lock-fill mono-input-icon"></i>
                            </div>
                            @if ($errors->has('password_baru'))
                                <div class="mono-warning">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                    <span>{{ ucfirst($errors->first('password_baru')) }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Konfirmasi Password Baru -->
                        <div class="mono-form-group">
                            <label class="mono-form-label" for="konfirmasi_password_baru">Konfirmasi Password Baru</label>
                            <div class="mono-input-wrapper">
                                <input type="password" class="mono-input" id="konfirmasi_password_baru"
                                    name="konfirmasi_password_baru" autocomplete="new-password"
                                    value="{{ old('konfirmasi_password_baru') }}" required>
                                <i class="bi bi-shield-fill-check mono-input-icon"></i>
                            </div>
                            @if ($errors->has('konfirmasi_password_baru'))
                                <div class="mono-warning">
                                    <i class="bi bi-exclamation-circle-fill"></i>
                                    <span>{{ ucfirst($errors->first('konfirmasi_password_baru')) }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Lihat Password Checkbox -->
                        <div class="mono-checkbox-wrapper">
                            <input class="mono-checkbox" type="checkbox" id="lihat_password">
                            <label class="mono-checkbox-label" for="lihat_password">
                                Lihat Password
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="btn-wrapper">
                            <button type="submit" name="ganti" class="mono-btn-primary">Ganti Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection