<div class="card"><div class="card-body row g-3">
    <div class="col-md-4"><label class="form-label" for="name">Item name</label><input class="form-control" id="name" name="name" value="{{ old('name', $part->name) }}" maxlength="255" required></div>
    <div class="col-md-3"><label class="form-label" for="sku">SKU</label><input class="form-control" id="sku" name="sku" value="{{ old('sku', $part->sku) }}" maxlength="100"></div>
    <div class="col-md-5"><label class="form-label" for="supplier_id">Supplier</label><select class="form-select" id="supplier_id" name="supplier_id"><option value="">No supplier linked</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $part->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $part->description) }}</textarea></div>
    <div class="col-md-4"><label class="form-label" for="cost_price">Unit cost (RM)</label><input class="form-control" id="cost_price" name="cost_price" type="number" min="0" step="0.01" value="{{ old('cost_price', $part->cost_price) }}" required></div>
    <div class="col-md-4"><label class="form-label" for="selling_price">Sale price (RM)</label><input class="form-control" id="selling_price" name="selling_price" type="number" min="0" step="0.01" value="{{ old('selling_price', $part->selling_price) }}" required></div>
    @if (! $part->exists)
        <div class="col-md-4"><label class="form-label" for="stock_quantity">Opening stock</label><input class="form-control" id="stock_quantity" name="stock_quantity" type="number" min="0" max="10000000" value="{{ old('stock_quantity', 0) }}" required></div>
    @endif
    <div class="col-md-4"><label class="form-label" for="reorder_level">Reorder at</label><input class="form-control" id="reorder_level" name="reorder_level" type="number" min="0" max="10000000" value="{{ old('reorder_level', $part->reorder_level ?? 0) }}" required></div>
</div></div>
@if ($part->exists)<div class="form-text mt-2">Update stock from the stock movements page so each change is recorded in the audit history.</div>@endif
