(() => {
    const form = document.querySelector('[data-debt-allocation-form]');
    if (!form) return;

    const items = form.querySelector('[data-allocation-items]');
    const template = form.querySelector('[data-allocation-template]');
    const search = form.querySelector('[data-allocation-search]');
    const add = form.querySelector('[data-allocation-add]');
    let nextIndex = items.children.length;

    const filterTargets = () => {
        const text = search.value.trim().toLocaleLowerCase('vi');
        for (const select of items.querySelectorAll('select')) {
            for (const option of select.options) {
                option.hidden = option.value !== '' && !option.selected &&
                    !option.textContent.toLocaleLowerCase('vi').includes(text);
            }
        }
    };
    search.addEventListener('input', filterTargets);
    add.addEventListener('click', () => {
        if (items.children.length >= 100) return;
        const item = template.content.cloneNode(true);
        for (const input of item.querySelectorAll('[name]')) {
            input.name = input.name.replace('__index__', String(nextIndex));
        }
        nextIndex++;
        items.append(item);
        filterTargets();
        items.lastElementChild.querySelector('select').focus();
    });
    items.addEventListener('click', event => {
        const remove = event.target.closest('[data-allocation-remove]');
        if (!remove) return;
        const item = remove.closest('[data-allocation-item]');
        if (items.children.length === 1) {
            item.querySelector('select').value = '';
            item.querySelector('input').value = '';
            filterTargets();
            return;
        }
        item.remove();
    });
})();
