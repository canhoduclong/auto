(() => {
    const dialog = document.getElementById('debt-policy-dialog');
    const form = dialog.querySelector('form');
    const defaultAction = form.action;
    document.querySelectorAll('.debt-policy-button').forEach(button => button.addEventListener('click', () => {
        form.action = button.dataset.policyAction || defaultAction;
        document.getElementById('debt-policy-key').value = button.dataset.key || '';
        document.getElementById('debt-policy-version').value = button.dataset.version;
        document.getElementById('debt-policy-name').textContent = button.dataset.name;
        document.getElementById('debt-policy-orders').value = button.dataset.orders;
        document.getElementById('debt-policy-limit').value = button.dataset.limit === '' ? '' : Number(button.dataset.limit).toLocaleString('vi-VN');
        document.getElementById('debt-policy-days').value = button.dataset.days;
        dialog.showModal();
    }));
    document.getElementById('debt-policy-close').addEventListener('click', () => dialog.close());
})();
