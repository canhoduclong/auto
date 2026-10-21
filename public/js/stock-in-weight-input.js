(function () {
    function clean(value) {
        const parts = String(value ?? '').replace(/[^0-9,]/g, '').split(',');
        const integer = (parts.shift() || '').slice(0, 9).replace(/^0+(?=\d)/, '');
        const fraction = parts.join('').slice(0, 3);
        return { integer, fraction, decimal: parts.length > 0 };
    }

    function formatInput(value) {
        const text = String(value ?? '').replace(/[^0-9,]/g, '');
        const decimal = text.includes(',');
        const parts = clean(value);
        if (!parts.integer && !decimal) return '';
        const integer = (parts.integer || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return integer + (decimal ? ',' + parts.fraction : '');
    }

    window.stockInWeightValue = value => Number(String(value ?? '').replace(/\./g, '').replace(',', '.')) || 0;
    window.stockInWeightFormat = value => (Number(value) || 0).toLocaleString('vi-VN', { maximumFractionDigits: 3 });
    window.stockInWeightInputFormat = formatInput;

    document.addEventListener('beforeinput', event => {
        if (!event.target.matches('.eq-weight')) return;
        if (event.inputType === 'insertText' && event.data && /[^0-9,.]/.test(event.data)) event.preventDefault();
    }, true);

    document.addEventListener('input', event => {
        const input = event.target;
        if (!input.matches('.eq-weight')) return;
        const position = input.selectionStart ?? input.value.length;
        const tokens = input.value.slice(0, position).replace(/[^0-9,]/g, '').length;
        input.value = formatInput(input.value);
        let cursor = 0, seen = 0;
        while (cursor < input.value.length && seen < tokens) {
            if (/[0-9,]/.test(input.value[cursor])) seen++;
            cursor++;
        }
        input.setSelectionRange(cursor, cursor);
    }, true);

    document.addEventListener('focusout', event => {
        const input = event.target;
        if (input.matches('.eq-weight') && input.value !== '') {
            input.value = stockInWeightFormat(stockInWeightValue(input.value));
        }
    });
})();
