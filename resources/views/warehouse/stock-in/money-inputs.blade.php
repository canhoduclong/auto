<script>
window.stockInMoneyValue = value => Number(String(value ?? '').replace(/\./g, '').replace(/\s/g, '')) || 0;
window.stockInMoneyFormat = value => (Number(value) || 0).toLocaleString('vi-VN', {maximumFractionDigits: 0});
document.addEventListener('input', function (event) {
    const input = event.target;
    if (!input.matches('.stock-money-input')) return;
    const position = input.selectionStart ?? input.value.length;
    const digitsBefore = input.value.slice(0, position).replace(/\D/g, '').length;
    const digits = input.value.replace(/\D/g, '');
    input.value = digits ? Number(digits).toLocaleString('vi-VN') : '';
    let cursor = 0, seen = 0;
    while (cursor < input.value.length && seen < digitsBefore) {
        if (/\d/.test(input.value[cursor])) seen++;
        cursor++;
    }
    input.setSelectionRange(cursor, cursor);
}, true);
document.addEventListener('submit', function (event) {
    event.target.querySelectorAll('.stock-money-input').forEach(input => {
        input.value = String(stockInMoneyValue(input.value));
    });
}, true);
</script>
