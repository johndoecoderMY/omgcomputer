@extends('layouts.app')

@section('title', $editing ? 'Edit shop product' : 'Add shop product')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" :title="$editing ? 'Edit shop product' : 'Add shop product'"
        :back-url="$editing ? route('shop.products.show', $product) : route('shop.products.index')"
        :back-label="$editing ? 'Back to product' : 'Back to products'" />
    <form class="card" method="POST" enctype="multipart/form-data" action="{{ $editing ? route('shop.products.update', $product) : route('shop.products.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="name">Product name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" maxlength="255" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sku">SKU</label>
                    <input class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" maxlength="100">
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="type">Category</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $product->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">To list a completed desktop, choose “Ready-built PC” and enable the shop listing below.</div>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
                        <option value="">No supplier linked</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">For a complete PC sourced from one supplier, record their cost here and resell it as a ready-built PC.</div>
                    @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-none" id="upgrade-options-field">
                    <label class="form-label" for="upgrade_option_ids">Supplier upgrade options</label>
                    <select class="form-select @error('upgrade_option_ids') is-invalid @enderror @error('upgrade_option_ids.*') is-invalid @enderror"
                        id="upgrade_option_ids" name="upgrade_option_ids[]" multiple
                        data-search-url="{{ route('shop.products.upgrade-options') }}">
                        @foreach ($selectedUpgradeOptions as $option)
                            <option value="{{ $option->id }}" selected>{{ $option->name }} · {{ $types[$option->type] ?? $option->type }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Choose the in-stock parts this supplier offers as customer-selectable upgrades. Customers can pick one option per category; each selected part is added at its listed price.</div>
                    @error('upgrade_option_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                    @error('upgrade_option_ids.*')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cost_price">Cost price (RM)</label>
                    <input class="form-control @error('cost_price') is-invalid @enderror" id="cost_price" name="cost_price" type="number" min="0" max="99999999" step="0.01" value="{{ old('cost_price', $product->cost_price) }}" required>
                    @error('cost_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="selling_price">Sale price (RM)</label>
                    <input class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" type="number" min="0" max="99999999" step="0.01" value="{{ old('selling_price', $product->selling_price) }}" required>
                    @error('selling_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @unless ($editing)
                    <div class="col-md-4">
                        <label class="form-label" for="stock_quantity">Opening stock</label>
                        <input class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity" type="number" min="0" max="10000000" value="{{ old('stock_quantity', 0) }}" required>
                        @error('stock_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @else
                    <div class="col-md-4">
                        <label class="form-label">Current stock</label>
                        <div class="form-control bg-body-tertiary">{{ $product->stock_quantity }} units</div>
                        <div class="form-text">Adjust stock from Inventory to keep movement history accurate.</div>
                    </div>
                @endunless
                <div class="col-md-4">
                    <label class="form-label" for="reorder_level">Reorder level</label>
                    <input class="form-control @error('reorder_level') is-invalid @enderror" id="reorder_level" name="reorder_level" type="number" min="0" max="10000000" value="{{ old('reorder_level', $product->reorder_level) }}" required>
                    @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" maxlength="2000">{{ old('description', $product->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="images">Product photos</label>
                    <input class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror" id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="form-text">The gallery can contain up to 10 JPEG, PNG, or WebP images (5 MB each). New photos are added to the product gallery.</div>
                    @error('images')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('images.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if ($editing && count($product->images ?? []))
                        <div class="row g-3 mt-1">
                            @foreach ($product->images as $image)
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100">
                                        <a class="glightbox d-block mb-2" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" data-gallery="product-{{ $product->id }}">
                                            <img class="img-fluid rounded" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" alt="{{ $product->name }}" style="height: 8rem; width: 100%; object-fit: cover">
                                        </a>
                                        <div class="form-check">
                                            <input class="form-check-input" id="remove-image-{{ $loop->index }}" name="remove_images[]" type="checkbox" value="{{ $image }}" @checked(in_array($image, old('remove_images', []), true))>
                                            <label class="form-check-label small" for="remove-image-{{ $loop->index }}">Remove photo</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" id="is_for_sale" name="is_for_sale" type="checkbox" value="1" @checked(old('is_for_sale', $product->is_for_sale))>
                        <label class="form-check-label" for="is_for_sale">List this item in the shop product grid</label>
                    </div>
                    <div class="form-text">Only in-stock items marked for sale appear in the product grid.</div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ $editing ? route('shop.products.show', $product) : route('shop.products.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">{{ $editing ? 'Save product' : 'Add product' }}</button>
        </div>
    </form>
</div>
<script>
(() => {
    const type = document.getElementById('type');
    const supplier = document.getElementById('supplier_id');
    const upgradeField = document.getElementById('upgrade-options-field');
    const upgradeSelect = window.jQuery('#upgrade_option_ids');
    if (!type || !supplier || !upgradeField || !upgradeSelect.length) return;

    const updateVisibility = () => upgradeField.classList.toggle('d-none', type.value !== 'ready_built_pc');
    updateVisibility();
    type.addEventListener('change', () => {
        updateVisibility();
        if (type.value !== 'ready_built_pc') upgradeSelect.val(null).trigger('change');
    });

    upgradeSelect.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search the selected supplier’s upgrade parts',
        minimumInputLength: 2,
        ajax: {
            url: upgradeSelect.data('search-url'),
            dataType: 'json',
            delay: 250,
            data: (params) => ({ q: params.term, supplier_id: supplier.value }),
            processResults: (payload) => payload,
        },
    });

    const updateSupplierAvailability = () => upgradeSelect.prop('disabled', !supplier.value);
    updateSupplierAvailability();
    supplier.addEventListener('change', () => {
        upgradeSelect.val(null).trigger('change');
        updateSupplierAvailability();
    });
})();
</script>
@endsection
