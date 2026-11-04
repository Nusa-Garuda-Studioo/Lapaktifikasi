@extends('layout')

@section('title', 'Menu Produk')

@section('content')

@if($success = Session::get('success'))
<script>
    Swal.fire({ title: "Berhasil", text: "{{ $success }}", icon: "success" });
</script>
@endif

@if($error = Session::get('error'))
<script>
    Swal.fire({ title: "Informasi", text: "{{ $error }}", icon: "info" });
</script>
@endif

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="ml-4">
                    <div class="row mt-3">
                        <div class="col">
                            <h5 class="text-primary">Menu Produk</h5>
                        </div>
                    </div>

                    @if(Auth::user()->role_id == 3)
                    <div class="row mt-2">
                        <div class="col">
                            <a href="{{ route('menu_produk_digital.create') }}" class="btn btn-success mb-3">
                                <i class="bi bi-plus-circle-fill"></i> Tambah Produk
                            </a>
                        </div>
                    </div>

                    {{-- Hint jika belum ada Produk Digital --}}
                    @if($semuaProduk->isEmpty())
                    <div class="alert alert-info py-2 d-flex align-items-center justify-content-between mb-3">
                        <span>
                            <i class="fas fa-lightbulb mr-1"></i>
                            <strong>Tips:</strong> Tambah produk (contoh: <em>Source Code Aplikasi, E-Book</em>), lalu atur
                            <a href="{{ route('digital.inventaris.index', ['tab' => 'tipe']) }}" class="font-weight-bold">Tipe Layanan</a> &amp; file digital.
                        </span>
                        <a href="{{ route('menu_produk_digital.create') }}" class="btn btn-info btn-sm ml-3 text-nowrap text-white">
                            <i class="fas fa-plus mr-1"></i> Tambah Produk
                        </a>
                    </div>
                    @endif
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped text-center" id="table-1">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Produk</th>
                                    <th>Deskripsi</th>
                                    @if(Auth::user()->role_id == 1)
                                        <th>Nama Toko</th>
                                    @endif
                                    <th>Status</th>
                                    <th>Tanggal Buat</th>
                                    <th>Tanggal Ubah</th>
                                    @if(Auth::user()->role_id == 3)
                                        <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @forelse($semuaProduk as $item)
                                <tr>
                                    <td>{{ $no++ }}</td>
                                    <td class="text-left">
                                        @if($item->gambar)
                                            <img src="{{ asset('assets/img/produk_premium/' . $item->gambar) }}"
                                                alt="cover" style="max-height:36px;border-radius:4px;margin-right:8px;vertical-align:middle;">
                                        @endif
                                        <strong>{{ $item->nama_produk }}</strong>
                                    </td>
                                    <td class="text-left" style="max-width:220px;">{{ $item->deskripsi ?? '-' }}</td>

                                    @if(Auth::user()->role_id == 1)
                                        <td>@if($item->toko) {{ $item->toko->nama_toko }} @else <span class="text-muted">Tanpa Toko</span> @endif</td>
                                    @endif

                                    <td>
                                        <span class="badge {{ $item->status == 'aktif' ? 'badge-success' : 'badge-danger' }}">
                                            {{ $item->status == 'aktif' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>{{ $item->created_at->format('d-m-Y H:i') }}</td>
                                    <td>{{ $item->updated_at->format('d-m-Y H:i') }}</td>

                                    @if(Auth::user()->role_id == 3)
                                    <td>
                                        <a href="{{ route('menu_produk_digital.edit', $item->id_produk) }}"
                                            class="btn btn-warning btn-sm text-white mb-1">
                                            <i class="bi bi-pen-fill"></i> Update
                                        </a>
                                        <form action="{{ route('menu_produk_digital.destroy', $item->id_produk) }}" method="post" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm mb-1"
                                                onclick="return confirm('Hapus produk \'{{ $item->nama_produk }}\'?')">
                                                <i class="bi bi-trash-fill"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                        Belum ada produk.
                                        @if(Auth::user()->role_id == 3)
                                            <a href="{{ route('menu_produk_digital.create') }}">Tambah produk pertama Anda</a>
                                        @endif
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

@endsection
