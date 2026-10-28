@push('scripts')
<script>
    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('.js-add-to-cart');
        if (!form) return;

        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const label = button.querySelector('span');
        button.disabled = true;
        label.textContent = 'Adding...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });
            const data = await response.json();

            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Unable to add this product.');
                throw new Error(firstError);
            }

            document.querySelectorAll('[data-cart-count]').forEach(function (counter) {
                counter.textContent = data.cartCount > 99 ? '99+' : data.cartCount;
            });
            label.textContent = 'Added!';
            window.setTimeout(function () { label.textContent = 'Add to cart'; }, 1500);
        } catch (error) {
            label.textContent = error.message || 'Try again';
            window.setTimeout(function () { label.textContent = 'Add to cart'; }, 2500);
        } finally {
            button.disabled = false;
        }
    });
</script>
@endpush
