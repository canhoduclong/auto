@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.add-to-cart').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const variantId = this.dataset.variantId;
            
            fetch('/cart/add', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    variant_id: variantId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Update cart count in header
                    const badge = document.querySelector('.cart-count');
                    if(badge) {
                        badge.textContent = data.cart_count;
                    }
                    
                    // Show success message
                    alert('Product added to cart!');
                }
            });
        });
    });
});
</script>
@endpush