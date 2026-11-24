document.querySelectorAll('[data-asset-document]').forEach(form => {
    const select = form.querySelector('[data-document-type]');
    select.addEventListener('change', () => {
        for (const fields of form.querySelectorAll('[data-document-fields]')) {
            fields.hidden = fields.dataset.documentFields !== select.value;
            fields.disabled = fields.hidden;
        }
    });
});
