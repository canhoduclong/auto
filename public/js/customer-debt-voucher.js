(() => {
    const selectAll = document.getElementById('voucher-select-all');
    if (!selectAll) return;

    const form = selectAll.form;
    const dates = Array.from(form.querySelectorAll('input[name="voucher_dates[]"]'));
    const update = () => {
        const checked = dates.filter(date => date.checked).length;
        selectAll.checked = checked === dates.length;
        selectAll.indeterminate = checked > 0 && checked < dates.length;
    };

    selectAll.closest('label').hidden = false;
    selectAll.addEventListener('change', () => {
        dates.forEach(date => { date.checked = selectAll.checked; });
        update();
    });
    dates.forEach(date => date.addEventListener('change', update));
    window.addEventListener('pageshow', update);
    form.addEventListener('formdata', event => {
        if (selectAll.checked) event.formData.delete('voucher_dates[]');
    });
    update();
})();
