@php
    $imagePath = match ($product->type) {
        'ready_built_pc', 'case' => 'assets/img/omg/ready-built-pc.jpg',
        'cpu', 'gpu', 'ram', 'motherboard', 'storage', 'power_supply', 'cooling' => 'assets/img/omg/repair-pc-components.jpg',
        default => 'assets/img/omg/computer-repair-placeholder.svg',
    };
@endphp
<img
    class="{{ $class ?? 'w-100 h-100 object-fit-cover' }}"
    src="{{ asset($imagePath) }}"
    alt="Representative stock photo for {{ $product->name }}. Actual product appearance may vary."
    loading="lazy"
    decoding="async"
>
