@extends('layouts.app')

@section('title', 'Manage builder parts')

@section('content')
<div class="container-fluid py-3"
    id="builder-parts-page"
    data-search-url="{{ $componentSearchUrl }}"
    data-store-url="{{ $componentStoreUrl }}"
    data-update-url="{{ $componentUpdateUrl }}"
    data-delete-url="{{ $componentDeleteUrl }}">
    <x-page-header eyebrow="Shop / Custom PC builder" title="Manage builder parts"
        description="Create, search, edit, and delete builder catalogue parts. These parts are separate from tracked inventory."
        :back-url="route('shop.custom-pc-builder')" :back-label="'Back to custom PC builder'" />

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1">Builder parts catalogue</h2>
                <p class="small text-body-secondary mb-0">Parts used by quotations cannot be deleted, preserving document history.</p>
            </div>
            <button class="btn btn-primary" type="button" id="builder-part-add">Add builder part</button>
        </div>
        <div class="card-body">
            <form class="row g-2 align-items-end mb-3" id="builder-part-search">
                <div class="col-md-6">
                    <label class="form-label" for="builder-part-query">Search name or SKU</label>
                    <input class="form-control" id="builder-part-query" name="search" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="builder-part-category">Category</label>
                    <select class="form-select" id="builder-part-category" name="category">
                        <option value="">All categories</option>
                        @foreach ($componentTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-outline-primary" type="submit">Search parts</button>
                </div>
            </form>

            <div id="builder-part-status" class="small text-body-secondary mb-2" role="status" aria-live="polite"></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Part</th>
                            <th>Category</th>
                            <th>Our cost</th>
                            <th>Customer price</th>
                            <th>Supplier</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="builder-part-list">
                        <tr><td colspan="6" class="text-body-secondary">Loading builder parts…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-outline-secondary" id="builder-part-previous" type="button">Previous</button>
                <span class="small text-body-secondary" id="builder-part-page"></span>
                <button class="btn btn-sm btn-outline-secondary" id="builder-part-next" type="button">Next</button>
            </div>

            <form id="builder-part-form" class="border rounded p-3 mt-4 d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0" id="builder-part-form-title">Add builder part</h2>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="builder-part-cancel">Cancel</button>
                </div>
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="builder-part-name">Part name</label>
                        <input class="form-control" id="builder-part-name" name="name" maxlength="255" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="builder-part-sku">SKU (optional)</label>
                        <input class="form-control" id="builder-part-sku" name="sku" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="builder-part-type">Category</label>
                        <select class="form-select" id="builder-part-type" name="type" required>
                            @foreach ($componentTypes as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="builder-part-description">Specifications / notes</label>
                        <textarea class="form-control" id="builder-part-description" name="description" rows="3" maxlength="5000" placeholder="Include compatibility details such as Socket: AM5"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="builder-part-cost">Our estimated cost (RM)</label>
                        <input class="form-control" id="builder-part-cost" name="cost_price" type="number" min="0" max="99999999" step="0.01" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="builder-part-price">Customer price (RM)</label>
                        <input class="form-control" id="builder-part-price" name="selling_price" type="number" min="0" max="99999999" step="0.01" required>
                    </div>
                    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="builder-part-form-status" class="small" role="status" aria-live="polite"></div>
                        <button class="btn btn-primary" type="submit" id="builder-part-save">Save part</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const page = document.getElementById('builder-parts-page');
        const searchForm = document.getElementById('builder-part-search');
        const partList = document.getElementById('builder-part-list');
        const status = document.getElementById('builder-part-status');
        const pageLabel = document.getElementById('builder-part-page');
        const partForm = document.getElementById('builder-part-form');
        const formStatus = document.getElementById('builder-part-form-status');
        const token = partForm.querySelector('input[name="_token"]').value;
        const categoryLabels = @json($componentTypes);
        let currentPage = 1;
        let lastPage = 1;
        let editingPartId = null;

        const loadParts = async (targetPage = 1) => {
            currentPage = targetPage;
            status.className = 'small text-body-secondary mb-2';
            status.textContent = 'Loading builder parts…';
            partList.replaceChildren();
            const params = new URLSearchParams(new FormData(searchForm));
            params.set('page', String(targetPage));

            try {
                const response = await fetch(`${page.dataset.searchUrl}?${params}`, {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Builder parts could not be loaded.');

                currentPage = result.meta.current_page;
                lastPage = result.meta.last_page;
                pageLabel.textContent = `Page ${currentPage} of ${lastPage}`;
                status.textContent = `${result.meta.total.toLocaleString()} builder parts`;
                document.getElementById('builder-part-previous').disabled = currentPage <= 1;
                document.getElementById('builder-part-next').disabled = currentPage >= lastPage;

                if (result.data.length === 0) {
                    const row = partList.insertRow();
                    const cell = row.insertCell();
                    cell.colSpan = 6;
                    cell.className = 'text-body-secondary';
                    cell.textContent = 'No builder parts match your search.';
                    return;
                }

                result.data.forEach((part) => {
                    const row = partList.insertRow();
                    const details = row.insertCell();
                    const name = document.createElement('div');
                    name.className = 'fw-semibold';
                    name.textContent = part.name;
                    const sku = document.createElement('div');
                    sku.className = 'small text-body-secondary';
                    sku.textContent = part.sku || 'No SKU';
                    details.append(name, sku);
                    row.insertCell().textContent = categoryLabels[part.type] || part.type;
                    row.insertCell().textContent = `RM ${Number(part.cost_price).toFixed(2)}`;
                    row.insertCell().textContent = `RM ${Number(part.selling_price).toFixed(2)}`;
                    row.insertCell().textContent = part.supplier || 'No supplier linked';
                    const actions = row.insertCell();
                    actions.className = 'text-end text-nowrap';
                    const editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'btn btn-sm btn-outline-primary me-1';
                    editButton.textContent = 'Edit';
                    editButton.addEventListener('click', () => showForm(part));
                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'button';
                    deleteButton.className = 'btn btn-sm btn-outline-danger';
                    deleteButton.textContent = 'Delete';
                    deleteButton.addEventListener('click', () => deletePart(part));
                    actions.append(editButton, deleteButton);
                });
            } catch (error) {
                status.className = 'small text-danger mb-2';
                status.textContent = error.message || 'Builder parts could not be loaded.';
            }
        };

        const showForm = (part = null) => {
            editingPartId = part?.id || null;
            partForm.reset();
            partForm.classList.remove('d-none');
            formStatus.textContent = '';
            formStatus.className = 'small';
            document.getElementById('builder-part-form-title').textContent = part ? 'Edit builder part' : 'Add builder part';
            document.getElementById('builder-part-save').textContent = part ? 'Save changes' : 'Add part';
            document.getElementById('builder-part-name').value = part?.name || '';
            document.getElementById('builder-part-sku').value = part?.sku || '';
            document.getElementById('builder-part-type').value = part?.type || Object.keys(categoryLabels)[0];
            document.getElementById('builder-part-description').value = part?.description || '';
            document.getElementById('builder-part-cost').value = part?.cost_price ?? 0;
            document.getElementById('builder-part-price').value = part?.selling_price ?? 0;
            partForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        const deletePart = async (part) => {
            if (!window.confirm(`Delete "${part.name}" from the builder catalogue?`)) return;
            const data = new FormData();
            data.append('_token', token);
            data.append('_method', 'DELETE');

            try {
                const response = await fetch(page.dataset.deleteUrl.replace('__PART__', String(part.id)), {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
                    body: data,
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'The builder part could not be deleted.');
                status.className = 'small text-success mb-2';
                status.textContent = result.message;
                await loadParts(currentPage);
            } catch (error) {
                status.className = 'small text-danger mb-2';
                status.textContent = error.message || 'The builder part could not be deleted.';
            }
        };

        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            loadParts();
        });
        document.getElementById('builder-part-previous').addEventListener('click', () => {
            if (currentPage > 1) loadParts(currentPage - 1);
        });
        document.getElementById('builder-part-next').addEventListener('click', () => {
            if (currentPage < lastPage) loadParts(currentPage + 1);
        });
        document.getElementById('builder-part-add').addEventListener('click', () => showForm());
        document.getElementById('builder-part-cancel').addEventListener('click', () => {
            partForm.classList.add('d-none');
            editingPartId = null;
        });
        partForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = document.getElementById('builder-part-save');
            submitButton.disabled = true;
            formStatus.className = 'small text-body-secondary';
            formStatus.textContent = editingPartId ? 'Saving changes…' : 'Adding builder part…';
            const data = new FormData(partForm);
            const url = editingPartId
                ? page.dataset.updateUrl.replace('__PART__', String(editingPartId))
                : page.dataset.storeUrl;
            if (editingPartId) data.append('_method', 'PUT');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
                    body: data,
                });
                const result = await response.json();
                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    throw new Error(validationMessage || result.message || 'The builder part could not be saved.');
                }
                partForm.classList.add('d-none');
                editingPartId = null;
                formStatus.className = 'small text-success';
                formStatus.textContent = result.message || `${result.name || result.part?.name} saved.`;
                await loadParts(currentPage);
            } catch (error) {
                formStatus.className = 'small text-danger';
                formStatus.textContent = error.message || 'The builder part could not be saved.';
            } finally {
                submitButton.disabled = false;
            }
        });

        loadParts();
    })();
</script>
@endpush
