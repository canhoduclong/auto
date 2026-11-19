(() => {
    const dialog = document.getElementById('debt-policy-dialog');
    const form = dialog.querySelector('form');
    const defaultAction = form.action;
    const cycle = form.querySelector('[data-policy-cycle]');
    const weekly = form.querySelector('[data-policy-weekly]');
    const monthly = form.querySelector('[data-policy-monthly]');
    const updateSchedule = () => {
        cycle.hidden = cycle.disabled = form.elements.payment_mode.value !== 'cycle';
        weekly.hidden = weekly.disabled = form.elements.cycle_type.value !== 'weekly';
        monthly.hidden = monthly.disabled = form.elements.cycle_type.value !== 'monthly';
    };
    form.elements.payment_mode.addEventListener('change', updateSchedule);
    form.elements.cycle_type.addEventListener('change', updateSchedule);
    document.querySelectorAll('.debt-policy-button').forEach(button => button.addEventListener('click', () => {
        form.action = button.dataset.policyAction || defaultAction;
        document.getElementById('debt-policy-key').value = button.dataset.key || '';
        document.getElementById('debt-policy-version').value = button.dataset.version;
        document.getElementById('debt-policy-name').textContent = button.dataset.name;
        document.getElementById('debt-policy-fixed').value = button.dataset.fixed === '' ? '' : Number(button.dataset.fixed).toLocaleString('vi-VN');
        document.getElementById('debt-policy-limit').value = button.dataset.limit === '' ? '' : Number(button.dataset.limit).toLocaleString('vi-VN');
        document.getElementById('debt-policy-threshold-days').value = button.dataset.thresholdDays;
        form.elements.payment_mode.value = button.dataset.paymentMode;
        form.elements.cycle_type.value = button.dataset.cycleType;
        form.elements.cycle_close_weekday.value = button.dataset.closeWeekday;
        form.elements.cycle_weekday.value = button.dataset.payWeekday;
        form.elements.cycle_close_monthday.value = button.dataset.closeMonthday;
        form.elements.cycle_monthday.value = button.dataset.payMonthday;
        updateSchedule();
        dialog.showModal();
    }));
    document.getElementById('debt-policy-close').addEventListener('click', () => dialog.close());
})();
