{{-- Partial: Breadcrumb Navigation (Bootstrap 4) --}}
@php
    $breadcrumbItems = $items ?? ($breadcrumbs ?? []);
@endphp

@if(!empty($breadcrumbItems) && count($breadcrumbItems) > 0)
<nav aria-label="breadcrumb" class="breadcrumb-container mb-3">
    <ol class="breadcrumb bg-transparent p-0 mb-0 d-flex align-items-center flex-wrap" style="font-size: 0.85rem;">
        @php
            $totalItems = count($breadcrumbItems);
            $currentIndex = 0;
        @endphp
        @foreach($breadcrumbItems as $labelKey => $val)
            @php
                $currentIndex++;
                $isLast = ($currentIndex === $totalItems);
                if (is_array($val)) {
                    $itemLabel = $val['label'] ?? $val['title'] ?? '';
                    $itemUrl = $val['url'] ?? $val['link'] ?? null;
                } else {
                    $itemLabel = $labelKey;
                    $itemUrl = $val;
                }
            @endphp
            @if(!$isLast && !empty($itemUrl))
                <li class="breadcrumb-item d-inline-flex align-items-center">
                    <a href="{{ $itemUrl }}" class="text-secondary text-decoration-none font-weight-medium">
                        @if($currentIndex === 1)
                            <i class="bi bi-house-door-fill mr-1" style="font-size: 0.85rem;"></i>
                        @endif
                        {{ $itemLabel }}
                    </a>
                </li>
            @else
                <li class="breadcrumb-item active d-inline-flex align-items-center text-dark font-weight-bold" aria-current="page">
                    @if($currentIndex === 1)
                        <i class="bi bi-house-door-fill mr-1" style="font-size: 0.85rem;"></i>
                    @endif
                    {{ $itemLabel }}
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endif

