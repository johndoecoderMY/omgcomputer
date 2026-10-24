@extends('layouts.app')

@section('title', $editing ? 'Edit shop product' : 'Add shop product')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="E-commerce" :title="$editing ? 'Edit shop product' : 'Add shop product'"
        :back-url="$editing ? route('shop.products.show', $product) : route('shop.products.index')"
        :back-label="$editing ? 'Back to product' : 'Back to products'" />
    <form class="card" method="POST" action="{{ $editing ? route('shop.products.update', $product) : route('shop.products.store') }}">
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
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
@endsection
