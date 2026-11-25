const assetCopyStatus = document.querySelector('[data-asset-copy-status]');
const assetCopyFallback = document.querySelector('[data-asset-copy-fallback]');

async function copyAssetConnectionText(text, confirmation) {
    const fallbackInput = assetCopyFallback.querySelector('input');
    fallbackInput.value = '';
    assetCopyFallback.hidden = true;
    try {
        await navigator.clipboard.writeText(text);
        assetCopyStatus.textContent = confirmation;
    } catch {
        fallbackInput.value = text;
        assetCopyFallback.hidden = false;
        fallbackInput.focus();
        fallbackInput.select();
        assetCopyStatus.textContent = 'Chọn và sao chép nội dung trong ô bên dưới.';
    }
}

document.querySelectorAll('[data-asset-copy-text]').forEach(button => {
    button.addEventListener('click', () => copyAssetConnectionText(button.dataset.assetCopyText, 'Đã sao chép URL OpenAPI.'));
});

document.querySelectorAll('[data-asset-copy-key]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;
        assetCopyFallback.hidden = true;
        assetCopyFallback.querySelector('input').value = '';
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {Accept: 'application/json'},
            });
            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Không thể lấy khóa kết nối.');
            }
            const {secret} = await response.json();
            await copyAssetConnectionText(secret, 'Đã sao chép khóa kết nối.');
        } catch (error) {
            assetCopyStatus.textContent = error.message || 'Không thể sao chép khóa.';
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelector('[data-asset-copy-issued]')?.addEventListener('click', async () => {
    const input = document.querySelector('[data-asset-copy-fallback-input]');
    try {
        await navigator.clipboard.writeText(input.value);
        assetCopyStatus.textContent = 'Đã sao chép khóa kết nối.';
    } catch {
        input.focus();
        input.select();
        assetCopyStatus.textContent = 'Khóa đã được chọn. Hãy sao chép nội dung trong ô.';
    }
});
