@php($productImages = $product->images ?? [])

@if (count($productImages))
    <div id="product-gallery-{{ $product->id }}" class="carousel slide w-100 h-100" aria-label="{{ __('Photos of :name', ['name' => $product->name]) }}">
        <div class="carousel-inner h-100">
            @foreach ($productImages as $image)
                @php($imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($image))
                <div class="carousel-item h-100 {{ $loop->first ? 'active' : '' }}">
                    <a class="glightbox d-flex align-items-center justify-content-center w-100 h-100" href="{{ $imageUrl }}" data-gallery="product-{{ $product->id }}" aria-label="{{ __('View photo :number of :name', ['number' => $loop->iteration, 'name' => $product->name]) }}">
                        <img class="w-100 h-100 rounded-4 object-fit-cover" src="{{ $imageUrl }}" alt="{{ $product->name }} - {{ __('photo :number', ['number' => $loop->iteration]) }}" @if (! $loop->first) loading="lazy" @endif>
                    </a>
                </div>
            @endforeach
        </div>
        @if (count($productImages) > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#product-gallery-{{ $product->id }}" data-bs-slide="prev" aria-label="{{ __('Previous photo') }}">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#product-gallery-{{ $product->id }}" data-bs-slide="next" aria-label="{{ __('Next photo') }}">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
        @endif
    </div>
@else
    @include('shop.public.products._stock-image', ['product' => $product, 'class' => $class ?? 'w-100 h-100 rounded-4 object-fit-cover'])
@endif
