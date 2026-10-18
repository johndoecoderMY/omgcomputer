<div class="card"><div class="card-body row g-3">
    <div class="col-md-4"><label class="form-label" for="supplier-name">Supplier name</label><input class="form-control" id="supplier-name" name="name" value="{{ old('name', $supplier->name) }}" required maxlength="255"></div>
    <div class="col-md-4"><label class="form-label" for="supplier-contact">Contact person</label><input class="form-control" id="supplier-contact" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" maxlength="255"></div>
    <div class="col-md-4"><label class="form-label" for="supplier-email">Email</label><input class="form-control" id="supplier-email" name="email" type="email" value="{{ old('email', $supplier->email) }}" maxlength="255"></div>
    <div class="col-md-4"><label class="form-label" for="supplier-phone">Phone</label><input class="form-control" id="supplier-phone" name="phone" value="{{ old('phone', $supplier->phone) }}" maxlength="50"></div>
    <div class="col-md-8"><label class="form-label" for="supplier-website">Website</label><input class="form-control" id="supplier-website" name="website" type="url" value="{{ old('website', $supplier->website) }}" maxlength="255"></div>
    <div class="col-12"><label class="form-label" for="supplier-notes">Notes</label><textarea class="form-control" id="supplier-notes" name="notes" rows="3" maxlength="2000">{{ old('notes', $supplier->notes) }}</textarea></div>
</div></div>
