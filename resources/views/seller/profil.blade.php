@extends('layout')

@section('title', 'Profil Toko Saya')

@section('content')

@if($success = Session::get('success'))
<script>
    Swal.fire({ title: "Berhasil", text: "{{ $success }}", icon: "success" });
</script>
@endif

<div class="row">
    <!-- Left Column: Shop Overview (Read-Only Info) -->
    <div class="col-md-4">
        <div class="card card-profile">
            <div class="card-header bg-dark text-white text-center p-4" style="flex-direction: column;">
                <div class="profile-widget-picture mb-3">
                    @if($toko->logo_toko)
                        <img src="{{ asset('assets/img/logo_toko/' . $toko->logo_toko) }}" alt="{{ $toko->nama_toko }}" class="rounded-circle img-thumbnail" width="100" height="100" style="object-fit: cover; width: 100px; height: 100px;">
                    @else
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white" style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <i class="fas fa-store"></i>
                        </div>
                    @endif
                </div>
                <h4>{{ $toko->nama_toko }}</h4>
                <div class="text-white-50"><small>ID Toko: #{{ $toko->id_toko }}</small></div>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Status Toko
                        <span class="badge badge-{{ $toko->status == 'aktif' ? 'success' : 'danger' }}">
                            {{ ucfirst($toko->status) }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Saldo Penjualan
                        <span class="font-weight-bold text-success">
                            Rp {{ number_format($toko->saldo, 0, ',', '.') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Skema Komisi
                        @if($toko->komisi_override !== null)
                            <span class="badge badge-info">Override: {{ $toko->komisi_override }}%</span>
                        @else
                            <span class="text-muted"><small>Global Default</small></span>
                        @endif
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Edit Shop Information Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Pengaturan Informasi Toko</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible show fade">
                        <div class="alert-body">
                            <button class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                            <strong>Gagal menyimpan perubahan:</strong>
                            <ul class="mb-0 mt-1 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form action="{{ url('seller/profil/update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group">
                        <label for="nama_toko">Nama Toko</label>
                        <input type="text" class="form-control @error('nama_toko') is-invalid @enderror" id="nama_toko" name="nama_toko" value="{{ old('nama_toko', $toko->nama_toko) }}" required>
                        @error('nama_toko')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="no_telp">No. Telepon / WhatsApp Kontak</label>
                                <input type="text" class="form-control @error('no_telp') is-invalid @enderror" id="no_telp" name="no_telp" value="{{ old('no_telp', $toko->no_telp) }}" required>
                                @error('no_telp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="akun_telegram">Username Telegram</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">@</span>
                                    </div>
                                    <input type="text" class="form-control @error('akun_telegram') is-invalid @enderror" id="akun_telegram" name="akun_telegram" value="{{ old('akun_telegram', $toko->akun_telegram) }}" placeholder="username" required>
                                    @error('akun_telegram')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Kontak telegram yang akan ditampilkan ke pembeli.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="logo_toko">Unggah Logo Toko Baru (Opsional)</label>
                        <input type="file" class="form-control-file" id="logo_toko" name="logo_toko">
                        <small class="form-text text-muted">
                            Ukuran maksimal 2MB. Format: jpeg, png, jpg, webp.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="informasi_toko">Informasi Tambahan Toko</label>
                        <textarea class="form-control" id="informasi_toko" name="informasi_toko" rows="4" placeholder="Tuliskan deskripsi toko, jadwal operasional, kebijakan, dll...">{{ old('informasi_toko', $toko->informasi_toko) }}</textarea>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Hubungkan Akun Telegram Seller -->
        <div class="mt-4">
            <x-telegram-link-card :user="auth()->user()" role="seller" card-id="telegram" />
        </div>
    </div>
</div>

@endsection
