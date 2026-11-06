@extends('layout')

@section('title', 'Menu Produk')

@section('content')

<div class="container mb-3">

    @if($error = Session::get('error'))
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

    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h4 class="mb-0 font-weight-bold text-dark">Checkout Pesanan</h4>
                </div>

                <div class="card-body p-4">

                    <form method="post" action="{{url('proses_checkout')}}">
                        @csrf

                        @if($user->nomor_telepon == '' || $user->user->name == '')
                        <div class="alert alert-warning mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill text-warning mr-2" style="font-size: 1.25rem;"></i>
                                <div>
                                    <strong>Profil Belum Lengkap!</strong> Lengkapi data profil Anda untuk membeli suatu produk.
                                    @if(Auth::check())
                                        <a href="{{ url('profile_customer/' . Auth::user()->id) }}" class="alert-link font-weight-bold d-block mt-1">
                                            <i class="bi bi-person-gear mr-1"></i> Lengkapi Profil Sekarang
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($errors->any())
                        <div class="alert alert-danger mb-4" role="alert">
                            <strong class="d-block mb-1">Terjadi kesalahan pada formulir:</strong>
                            <ul class="mb-0 pl-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Hidden inputs untuk menjaga payload POST tetap identik --}}
                        <input type="hidden" name="id" value="{{ $produk->id_produk }}">
                        <input type="hidden" name="nama_produk" value="{{ $produk->nama_produk }}">
                        <input type="hidden" name="deskripsi" value="{{ $produk->deskripsi }}">
                        <input type="hidden" name="qty" value="1">
                        <input type="hidden" name="harga" value="{{ number_format($produk->harga, 0, ',', '.') }}">

                        {{-- Inline validation error feedback --}}
                        @error('id')
                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror
                        @error('nama_produk')
                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror
                        @error('deskripsi')
                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror
                        @error('qty')
                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror
                        @error('harga')
                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror

                        {{-- Kartu Ringkasan Pesanan --}}
                        <div class="card bg-light border mb-4">
                            <div class="card-header bg-white border-bottom py-2">
                                <h5 class="mb-0 font-weight-bold text-dark" style="font-size: 1rem;">
                                    <i class="bi bi-receipt mr-1 text-primary"></i> Ringkasan Pesanan
                                </h5>
                            </div>
                            <div class="card-body p-3 p-md-4">
                                <div class="row mb-3">
                                    <div class="col-12 col-md-3 text-muted font-weight-bold mb-1 mb-md-0">Nama Produk</div>
                                    <div class="col-12 col-md-9 font-weight-bold text-dark">{{ $produk->nama_produk }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-12 col-md-3 text-muted font-weight-bold mb-1 mb-md-0">Deskripsi</div>
                                    <div class="col-12 col-md-9 text-secondary">{{ $produk->deskripsi ?: '-' }}</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-12 col-md-3 text-muted font-weight-bold mb-1 mb-md-0">Jumlah (Qty)</div>
                                    <div class="col-12 col-md-9 text-dark">
                                        <span class="badge badge-secondary px-2 py-1 font-weight-bold">1 item</span>
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="row align-items-center">
                                    <div class="col-12 col-md-3 text-muted font-weight-bold mb-1 mb-md-0">Total Pembayaran</div>
                                    <div class="col-12 col-md-9">
                                        <span class="h4 font-weight-bold text-primary mb-0">
                                            Rp {{ number_format($produk->harga, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Aksi & Petunjuk --}}
                        <div class="pt-3 border-top">
                            <div class="d-flex flex-column-reverse flex-sm-row justify-content-sm-between align-items-sm-center">
                                <a href="{{ url()->previous() && url()->previous() !== url()->current() ? url()->previous() : url('/menu_produk') }}" 
                                   class="btn btn-outline-secondary btn-block d-sm-inline-block mt-3 mt-sm-0 px-4 font-weight-bold"
                                   style="width: auto;">
                                    <i class="bi bi-arrow-left mr-1"></i> Kembali
                                </a>

                                <div class="text-sm-right w-100 w-sm-auto">
                                    @if($user->nomor_telepon != '' && $user->user->name != '')
                                        <button type="submit" name="proses_checkout" class="btn btn-primary btn-block d-sm-inline-block font-weight-bold px-4">
                                            Checkout Sekarang <i class="bi bi-arrow-right ml-1"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-block d-sm-inline-block font-weight-bold px-4" disabled title="Lengkapi profil Anda untuk melanjutkan">
                                            Checkout Sekarang <i class="bi bi-arrow-right ml-1"></i>
                                        </button>
                                    @endif

                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-clock-history mr-1"></i> Stok produk diamankan selama 15 menit setelah checkout.
                                    </small>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
