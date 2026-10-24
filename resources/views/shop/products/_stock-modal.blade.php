<div class="modal fade" id="shop-stock-modal" tabindex="-1" aria-labelledby="shop-stock-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="shop-stock-form" method="POST">
            @csrf
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="shop-stock-modal-title">Adjust stock</h2>
                    <p class="small text-body-secondary mb-0" id="shop-stock-product-name"></p>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-3">Current stock: <strong id="shop-stock-current"></strong></p>
                <input type="hidden" name="view" value="{{ $stockView }}">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <input type="hidden" name="type" value="{{ $filters['type'] }}">
                <input type="hidden" name="sale" value="{{ $filters['sale'] }}">
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <div class="mb-3">
                    <label class="form-label" for="shop-stock-direction">Adjustment</label>
                    <select class="form-select" id="shop-stock-direction" name="direction" required>
                        <option value="receive">Add quantity</option>
                        <option value="use">Remove quantity</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="shop-stock-quantity">Quantity</label>
                    <input class="form-control" id="shop-stock-quantity" name="quantity" type="number" min="1" max="10000000" step="1" required>
                </div>
                <div>
                    <label class="form-label" for="shop-stock-note">Note <span class="text-body-secondary">(optional)</span></label>
                    <input class="form-control" id="shop-stock-note" name="note" type="text" maxlength="1000" placeholder="Reason for this stock change">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Update stock</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('shop-stock-modal');
        const form = document.getElementById('shop-stock-form');
        if (!modal || !form) {
            return;
        }

        modal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (!button) {
                return;
            }

            form.action = button.dataset.stockAction;
            document.getElementById('shop-stock-product-name').textContent = button.dataset.stockName;
            document.getElementById('shop-stock-current').textContent = button.dataset.stockCurrent;
            document.getElementById('shop-stock-quantity').value = '';
            document.getElementById('shop-stock-note').value = '';
            document.getElementById('shop-stock-direction').value = 'receive';
        });
    });
</script>
@endpush
