<div class="mb-3">
    <div class="form-label fw-semibold">File đính kèm</div>
    <input id="task-attachments" type="file" name="attachments[]" class="visually-hidden" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xlsx,.zip" aria-label="Chọn file đính kèm" aria-describedby="task-attachments-help">
    <button id="task-add-attachments" type="button" class="btn btn-outline-primary">
        <i class="ph-upload-simple me-1" aria-hidden="true"></i> Tải file lên
    </button>
    <small id="task-attachments-help" class="text-muted d-block mt-2">Chọn nhiều file cùng lúc hoặc bấm thêm lần nữa. JPG, PNG, PDF, Word, Excel, ZIP; tối đa 10MB mỗi file. File được tải lên khi lưu công việc.</small>
    <ul id="task-attachment-list" class="list-unstyled mb-0 mt-2" aria-live="polite"></ul>
    @error('attachments')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @error('attachments.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

<script>
(() => {
    const input = document.getElementById('task-attachments');
    const list = document.getElementById('task-attachment-list');
    let files = [];

    function render() {
        const transfer = new DataTransfer();
        files.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
        list.replaceChildren();
        files.forEach((file, index) => {
            const row = document.createElement('li');
            row.className = 'd-flex align-items-center gap-2 py-1';
            const label = document.createElement('span');
            label.className = 'small text-break';
            label.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger ms-auto';
            remove.textContent = 'Xóa';
            remove.setAttribute('aria-label', `Xóa file ${file.name}`);
            remove.addEventListener('click', () => {
                files.splice(index, 1);
                render();
            });
            row.append(label, remove);
            list.append(row);
        });
    }

    document.getElementById('task-add-attachments').addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
        for (const file of input.files) {
            if (!files.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified)) {
                files.push(file);
            }
        }
        render();
    });
})();
</script>
