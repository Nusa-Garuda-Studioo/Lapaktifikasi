@extends('layout')

@section('title', 'Laporan Customer')

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
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Daftar Laporan Masalah Customer</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table-1">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pengirim</th>
                                <th>Judul Laporan</th>
                                <th>Tanggal Masuk</th>
                                <th>Lampiran</th>
                                <th>Status Terkini</th>
                                <th>Ubah Status</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporan as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->user->name ?? '-' }}</strong> <br>
                                    <small class="text-muted mb-1 d-block">{{ $item->user->email }}</small>
                                    @php
                                        $phone = $item->user->customer->first()?->nomor_telepon;
                                    @endphp
                                    @if($phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $phone) }}" target="_blank" class="badge badge-success text-white">
                                            <i class="bi bi-whatsapp mr-1"></i>{{ $phone }}
                                        </a>
                                    @else
                                        <span class="badge badge-secondary text-white">
                                            <i class="bi bi-telephone-x-fill mr-1"></i>No WA
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $item->judul }}</td>
                                <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($item->gambar)
                                    <a href="{{ asset('assets/img/laporan/' . $item->gambar) }}" target="_blank">
                                        <img src="{{ asset('assets/img/laporan/' . $item->gambar) }}" alt="lampiran" class="rounded" style="max-height: 45px; max-width: 45px; object-fit: cover;">
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
                                    <form action="{{ route('admin.laporan.status', $item->id) }}" method="POST" class="form-inline">
                                        @csrf
                                        <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                            <option value="pending" {{ $item->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="proses" {{ $item->status == 'proses' ? 'selected' : '' }}>Diproses</option>
                                            <option value="selesai" {{ $item->status == 'selesai' ? 'selected' : '' }}>Selesai</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewAdminDetailLaporan({{ json_encode($item) }}, '{{ $item->user->name ?? '-' }}', '{{ $item->user->email }}', '{{ $item->user->customer->first()?->nomor_telepon }}')">Detail</button>
                                    <a href="{{ route('laporan.show', $item->id) }}" class="btn btn-sm btn-primary ml-1" title="Buka Thread Percakapan">
                                        <i class="bi bi-chat-dots-fill"></i> Thread
                                        @if($item->pesan && $item->pesan->count() > 0)
                                            <span class="badge badge-light text-dark ml-1">{{ $item->pesan->count() }}</span>
                                        @endif
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada laporan kendala dari customer.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Laporan Admin -->
<div class="modal fade" id="adminDetailLaporanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog text-dark" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Kendala Customer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group border-bottom pb-2">
                    <label class="font-weight-bold mb-1">Pengirim / Customer</label>
                    <p id="admin-modal-user" class="mb-0"></p>
                </div>
                <div class="form-group border-bottom pb-2">
                    <label class="font-weight-bold mb-1">Judul Kendala</label>
                    <p id="admin-modal-judul" class="text-primary mb-0 font-weight-bold"></p>
                </div>
                <div class="form-group border-bottom pb-2">
                    <label class="font-weight-bold mb-1">Deskripsi Masalah</label>
                    <p id="admin-modal-deskripsi" class="border p-2 bg-light rounded" style="white-space: pre-wrap;"></p>
                </div>
                <div class="form-group" id="admin-modal-gambar-group">
                    <label class="font-weight-bold mb-1">Lampiran Screenshot</label>
                    <div>
                        <a id="admin-modal-gambar-link" href="#" target="_blank">
                            <img id="admin-modal-gambar-img" src="" alt="lampiran" class="img-fluid rounded border" style="max-height: 250px;">
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
    function viewAdminDetailLaporan(laporan, userName, userEmail, userPhone) {
        const phoneDisplay = userPhone ? `<br><a href="https://wa.me/${userPhone.replace(/[^0-9]/g, '')}" target="_blank" class="text-success font-weight-bold"><i class="bi bi-whatsapp mr-1"></i>${userPhone}</a>` : '';
        document.getElementById('admin-modal-user').innerHTML = `<strong>${userName}</strong> (${userEmail})${phoneDisplay}`;
        document.getElementById('admin-modal-judul').innerText = laporan.judul;
        document.getElementById('admin-modal-deskripsi').innerText = laporan.deskripsi;

        const gambarGroup = document.getElementById('admin-modal-gambar-group');
        if (laporan.gambar) {
            gambarGroup.style.display = 'block';
            document.getElementById('admin-modal-gambar-link').href = "{{ asset('assets/img/laporan') }}/" + laporan.gambar;
            document.getElementById('admin-modal-gambar-img').src = "{{ asset('assets/img/laporan') }}/" + laporan.gambar;
        } else {
            gambarGroup.style.display = 'none';
        }

        $('#adminDetailLaporanModal').modal('show');
    }
</script>

@endsection
