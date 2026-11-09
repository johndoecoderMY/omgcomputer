<div class="card-body p-3 p-lg-4">
    <div class="row g-2 align-items-end">
        <div class="col-12 {{ $idPrefix === 'shop-grid' ? 'col-md' : '' }}">
            <label class="form-label small fw-semibold" for="{{ $idPrefix }}-search">Search products</label>
            <input class="form-control" id="{{ $idPrefix }}-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search by product name or description">
        </div>
        <div class="{{ $idPrefix === 'shop-grid' ? 'col-6 col-md-3' : 'col-12' }}">
            <label class="form-label small fw-semibold" for="{{ $idPrefix }}-type">Category</label>
            <select class="form-select" id="{{ $idPrefix }}-type" name="type">
                <option value="">All categories</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="{{ $idPrefix === 'shop-grid' ? 'col-6 col-md-3' : 'col-12' }}">
            <label class="form-label small fw-semibold" for="{{ $idPrefix }}-sort">Sort by</label>
            <select class="form-select" id="{{ $idPrefix }}-sort" name="sort">
                <option value="name" @selected($filters['sort'] === 'name')>Name</option>
                <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                <option value="price-low" @selected($filters['sort'] === 'price-low')>Price: low to high</option>
                <option value="price-high" @selected($filters['sort'] === 'price-high')>Price: high to low</option>
            </select>
        </div>
        <div class="col-12 {{ $idPrefix === 'shop-grid' ? 'col-md-auto' : '' }} d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Search</button>
            @if ($filters['search'] || $filters['type'] || $filters['sort'] !== 'name')
                <a class="btn btn-outline-secondary" href="{{ route('shop.public') }}">Clear</a>
            @endif
        </div>
    </div>
</div>
