@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Quản lý danh mục</h2>

    @php
        $currentUser = auth()->user();
        $canBulkDelete = $currentUser && ($currentUser->hasRole('admin') || $currentUser->hasPermission('categories.delete'));
    @endphp

    @can('create', App\Models\Category::class)
        <a href="{{ route('categories.create') }}" class="btn btn-success mb-3">Thêm danh mục mới</a>
    @endcan

    @if($canBulkDelete)
        <button type="submit" form="bulk-delete-form" class="btn btn-outline-danger mb-3" id="bulk-delete-button">Xóa đã chọn</button>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="bulk-delete-form" action="{{ route('categories.bulk-delete') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
        <div id="bulk-delete-inputs"></div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                @if($canBulkDelete)
                    <th style="width: 1%; white-space: nowrap;">
                        <input type="checkbox" id="select-all-categories" class="form-check-input">
                    </th>
                @endif
                <th>ID</th>
                <th>Tên danh mục</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
            <tr>
                @if($canBulkDelete)
                    <td>
                        @can('delete', $category)
                            <input type="checkbox" class="form-check-input category-checkbox" value="{{ $category->id }}">
                        @endcan
                    </td>
                @endif
                <td>{{ $category->id }}</td>
                <td>{{ $category->name }}</td>                
                <td>
                    @can('update', $category)
                        <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-primary btn-sm">Sửa</a>
                    @endcan
                    @can('delete', $category)
                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa không?')">Xóa</button>
                    </form>
                    @endcan
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $categories->links() }}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('select-all-categories');
    var bulkDeleteButton = document.getElementById('bulk-delete-button');
    var bulkDeleteForm = document.getElementById('bulk-delete-form');
    var bulkDeleteInputs = document.getElementById('bulk-delete-inputs');

    function getCategoryCheckboxes() {
        return Array.from(document.querySelectorAll('.category-checkbox'));
    }

    function syncBulkDeleteButton() {
        if (!bulkDeleteButton) {
            return;
        }

        var hasSelection = getCategoryCheckboxes().some(function (checkbox) {
            return checkbox.checked;
        });

        bulkDeleteButton.disabled = !hasSelection;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            getCategoryCheckboxes().forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });

            syncBulkDeleteButton();
        });
    }

    getCategoryCheckboxes().forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var checkboxes = getCategoryCheckboxes();
            var checkedCount = checkboxes.filter(function (item) {
                return item.checked;
            }).length;

            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            }

            syncBulkDeleteButton();
        });
    });

    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function (event) {
            var selectedIds = getCategoryCheckboxes()
                .filter(function (checkbox) {
                    return checkbox.checked;
                })
                .map(function (checkbox) {
                    return checkbox.value;
                });

            if (selectedIds.length === 0) {
                event.preventDefault();
                alert('Vui lòng chọn ít nhất một danh mục để xóa.');
                return;
            }

            if (!window.confirm('Bạn có chắc chắn muốn xóa các danh mục đã chọn không?')) {
                event.preventDefault();
                return;
            }

            bulkDeleteInputs.innerHTML = '';

            selectedIds.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'category_ids[]';
                input.value = id;
                bulkDeleteInputs.appendChild(input);
            });
        });
    }

    syncBulkDeleteButton();
});
</script>
@endpush