@php
    $productSearchText = \Illuminate\Support\Str::lower($product->name.' '.$product->description);
    $isStorageProduct = \Illuminate\Support\Str::contains($productSearchText, ['nvme', 'ssd', 'solid state', 'storage drive']);
    $componentTypes = ['cpu', 'gpu', 'ram', 'motherboard', 'storage', 'power_supply', 'cooling'];
    $imagePath = match (true) {
        in_array($product->type, ['ready_built_pc', 'case'], true) => 'assets/img/omg/ready-built-pc.jpg',
        in_array($product->type, $componentTypes, true), $isStorageProduct => 'assets/img/omg/repair-pc-components.jpg',
        default => 'assets/img/omg/computer-repair-placeholder.svg',
    };
@endphp
<img
    class="{{ $class ?? 'w-100 h-100 object-fit-cover' }}"
    src="{{ asset($imagePath) }}"
    alt="{{ __('Representative stock photo for :name. Actual product appearance may vary.', ['name' => $product->name]) }}"
    loading="lazy"
    decoding="async"
>
