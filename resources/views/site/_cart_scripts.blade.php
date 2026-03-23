@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.siteCartManagerInitialized) {
        return;
    }

    window.siteCartManagerInitialized = true;

    function showCartMessage(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type === 'error' ? 'error' : 'success');
            return;
        }

        window.alert(message);
    }

    function updateCartCount(cartCount) {
        document.querySelectorAll('.js-cart-count').forEach(function(badge) {
            badge.textContent = cartCount;
            badge.classList.toggle('d-none', Number(cartCount) <= 0);
        });
    }

    window.siteCart = {
        addVariant: function(variantId, quantity) {
            return fetch('/cart/add', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    variant_id: variantId,
                    quantity: quantity || 1
                })
            })
            .then(async function(response) {
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Khong the them san pham vao gio hang.');
                }

                updateCartCount(data.cart_count || 0);
                return data;
            });
        },
        updateCartCount: updateCartCount,
    };

    document.addEventListener('click', function(event) {
        const button = event.target.closest('.add-to-cart');
        if (!button) {
            return;
        }

        event.preventDefault();

        if (button.disabled) {
            return;
        }

        const variantId = button.dataset.variantId;
        const quantity = Number(button.dataset.quantity || 1);

        if (!variantId) {
            return;
        }

        const originalDisabled = button.disabled;
        button.disabled = true;

        window.siteCart.addVariant(variantId, quantity)
            .then(function(data) {
                showCartMessage(data.message || 'Da them san pham vao gio hang.', 'success');
            })
            .catch(function(error) {
                showCartMessage(error.message || 'Khong the them san pham vao gio hang.', 'error');
            })
            .finally(function() {
                button.disabled = originalDisabled;
            });
    });
});
</script>
@endpush