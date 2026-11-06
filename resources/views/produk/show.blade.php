@extends('layout')

@section('title', 'Lihat Screenshots Produk')

@section('content')

<div class="container py-2">
    @include('partials.breadcrumb', ['items' => [
        'Beranda' => url('/'),
        'Menu Produk' => url('/menu_produk'),
        'Screenshots Produk' => null
    ]])

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="text-dark font-weight-bold mb-0">
                        <i class="bi bi-images text-primary mr-2"></i> Screenshots Produk
                    </h5>
                </div>

                <div class="card-body">
                    <div id="carouselExampleIndicators3" class="carousel slide mb-4" data-ride="carousel">
                        <ol class="carousel-indicators">
                            @foreach($getFiles as $index => $img)
                                <li data-target="#carouselExampleIndicators3" data-slide-to="{{ $index }}" class="{{ $index == 0 ? 'active' : '' }}"></li>
                            @endforeach
                        </ol>
                        <div class="carousel-inner rounded">
                            @foreach($getFiles as $index => $img)
                            <div class="carousel-item {{ $index == 0 ? 'active' : '' }}">
                                <img src="{{ asset('assets/' . $folderExtract . '/' . $img) }}"
                                    class="rounded mx-auto d-block w-100" style="max-height: 540px; object-fit: contain; background: #000;" alt="screenshots">
                            </div>
                            @endforeach
                        </div>
                        @if(count($getFiles) > 1)
                        <a class="carousel-control-prev" href="#carouselExampleIndicators3" role="button"
                            data-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#carouselExampleIndicators3" role="button"
                            data-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                        @endif
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <a href="{{ url()->previous() && url()->previous() !== url()->current() ? url()->previous() : url('/menu_produk') }}" class="btn btn-outline-secondary px-4 font-weight-bold">
                            <i class="bi bi-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
