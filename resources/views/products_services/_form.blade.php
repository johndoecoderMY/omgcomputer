<div class="card"><div class="card-body row g-3">
    <div class="col-md-6"><label class="form-label" for="name">Name</label><input class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" maxlength="255" required></div>
    <div class="col-md-6"><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category" required><option value="">Choose category</option>@foreach ($categories as $key => $label)<option value="{{ $key }}" @selected(old('category', $product->category) === $key)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label" for="scope">Scope / description</label><textarea class="form-control" id="scope" name="scope" rows="3" maxlength="2000" required>{{ old('scope', $product->scope) }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="audience">Target customer</label><input class="form-control" id="audience" name="audience" value="{{ old('audience', $product->audience) }}" maxlength="255" required></div>
    <div class="col-md-3"><label class="form-label" for="price">Displayed price</label><input class="form-control" id="price" name="price" value="{{ old('price', $product->price) }}" maxlength="80" placeholder="e.g. RM60–100" required></div>
    <div class="col-md-3"><label class="form-label" for="billing_price">Starting price (RM)</label><input class="form-control" id="billing_price" name="billing_price" type="number" min="0" step="0.01" value="{{ old('billing_price', $product->billing_price) }}" required></div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true))>
            <label class="form-check-label" for="is_active">Active and available for public bookings</label>
        </div>
    </div>
</div></div>
