(() => {
    if (window.DiscountMoney) return;
    const parse = value => Math.max(0, Number(String(value ?? '').replace(/\./g, '').replace(',', '.')) || 0);
    const format = value => new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 2}).format(Number(value) || 0);
    window.DiscountMoney = {parse, format};
    const selector = '[data-discount-money]';
    document.querySelectorAll(selector).forEach(input => {
        input.value = format(Number(input.value) || 0);
    });
    document.addEventListener('input', event => {
        const input = event.target;
        if (!input.matches(selector)) return;
        const digitsBefore = input.value.slice(0, input.selectionStart).replace(/\D/g, '').length;
        input.value = input.value === '' ? '' : format(parse(input.value));
        let position = 0, digits = 0;
        while (position < input.value.length && digits < digitsBefore) {
            if (/\d/.test(input.value[position])) digits++;
            position++;
        }
        input.setSelectionRange(position, position);
    }, true);
    document.addEventListener('formdata', event => {
        event.target.querySelectorAll(selector).forEach(input => {
            if (input.name && !input.disabled) event.formData.set(input.name, String(parse(input.value)));
        });
    });
})();
