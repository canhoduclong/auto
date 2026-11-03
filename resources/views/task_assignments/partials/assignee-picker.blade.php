<div id="task-assignee-picker" data-preferences-key="task-assignee-favorites:{{ auth()->id() }}">
    <div class="row g-2 mb-2">
        <div class="col-sm-7">
            <label for="assignee-search" class="form-label small">Tìm thành viên</label>
            <input id="assignee-search" type="search" class="form-control" placeholder="Nhập tên, có thể gõ không dấu…" autocomplete="off">
        </div>
        <div class="col-sm-5">
            <label for="assignee-sort" class="form-label small">Sắp xếp</label>
            <select id="assignee-sort" class="form-select">
                <option value="priority">Đã chọn / Đánh dấu lên đầu</option>
                <option value="name">Tên A → Z</option>
            </select>
        </div>
    </div>
    <p class="small text-muted mb-2">Bấm ☆ để đánh dấu người thường giao việc. Dấu sao được lưu trên trình duyệt này.</p>
    <div id="assignee-count" class="small mb-2" aria-live="polite"></div>
    <div class="row g-2" id="assignee-options">
        @foreach($allowedAssignees as $person)
        <div class="col-sm-6 col-md-4" data-assignee-id="{{ $person->id }}" data-assignee-name="{{ $person->name }}">
            <div class="d-flex align-items-center gap-1 p-2 border rounded h-100" data-assignee-card style="background:#fff">
                <label class="d-flex align-items-center gap-2 flex-grow-1 mb-0" style="cursor:pointer;min-width:0">
                    <input type="checkbox" name="assignee_ids[]" value="{{ $person->id }}" @checked(in_array($person->id, $selectedAssigneeIds))>
                    <span class="small fw-semibold text-break">{{ $person->name }}</span>
                </label>
                <button type="button" class="btn btn-sm px-1" data-assignee-favorite aria-label="Đánh dấu {{ $person->name }}" aria-pressed="false" title="Đánh dấu người thường giao việc">☆</button>
            </div>
        </div>
        @endforeach
    </div>
    <p id="assignee-empty" class="small text-muted mt-2 mb-0" hidden>Không tìm thấy thành viên phù hợp.</p>
</div>

<script>
(() => {
    const picker = document.getElementById('task-assignee-picker');
    const options = picker.querySelector('#assignee-options');
    const rows = [...options.children];
    const search = picker.querySelector('#assignee-search');
    const sort = picker.querySelector('#assignee-sort');
    const key = picker.dataset.preferencesKey;
    let favorites = new Set();
    try {
        const saved = JSON.parse(localStorage.getItem(key) || '[]');
        if (Array.isArray(saved)) favorites = new Set(saved.map(String));
    } catch (_) {}
    const normalize = value => value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
    const checkbox = row => row.querySelector('input[type=checkbox]');
    const priority = row => (checkbox(row).checked ? 2 : 0) + (favorites.has(row.dataset.assigneeId) ? 1 : 0);

    function render() {
        const term = normalize(search.value.trim());
        let visible = 0;
        rows.sort((a, b) => (sort.value === 'priority' ? priority(b) - priority(a) : 0) || a.dataset.assigneeName.localeCompare(b.dataset.assigneeName, 'vi'));
        for (const row of rows) {
            const selected = checkbox(row).checked;
            const favorite = favorites.has(row.dataset.assigneeId);
            const button = row.querySelector('[data-assignee-favorite]');
            button.textContent = favorite ? '★' : '☆';
            button.classList.toggle('text-warning', favorite);
            button.setAttribute('aria-pressed', String(favorite));
            button.setAttribute('aria-label', `${favorite ? 'Bỏ đánh dấu' : 'Đánh dấu'} ${row.dataset.assigneeName}`);
            row.querySelector('[data-assignee-card]').classList.toggle('border-primary', selected);
            row.hidden = !normalize(row.dataset.assigneeName).includes(term);
            if (!row.hidden) visible++;
            options.append(row);
        }
        picker.querySelector('#assignee-count').textContent = `Đã chọn ${rows.filter(row => checkbox(row).checked).length} người · Hiển thị ${visible}/${rows.length}`;
        picker.querySelector('#assignee-empty').hidden = visible !== 0;
    }

    for (const row of rows) {
        checkbox(row).addEventListener('change', render);
        row.querySelector('[data-assignee-favorite]').addEventListener('click', () => {
            const id = row.dataset.assigneeId;
            if (favorites.has(id)) favorites.delete(id); else favorites.add(id);
            try { localStorage.setItem(key, JSON.stringify([...favorites])); } catch (_) {}
            render();
        });
    }
    search.addEventListener('input', render);
    sort.addEventListener('change', render);
    render();
})();
</script>
