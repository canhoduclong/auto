@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Danh sách biến thể sản phẩm</h4>
        <a href="{{ route('product-variants.create') }}" class="btn btn-success">+ Thêm biến thể mới</a>
    </div>
    <form method="get" class="mb-3" id="filter-form">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control" placeholder="Tìm kiếm SKU, size, chất lượng, tên sản phẩm..." value="{{ request('q') }}">
            </div>
            <div class="col-md-3">
                <select name="product_id" class="form-control">
                    <option value="">-- Lọc theo sản phẩm --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Lọc</button>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-2">
                <select name="per_page" class="form-control">
                    <option value="5" {{ request('per_page') == 5 ? 'selected' : '' }}>5 / trang</option>
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 / trang</option>
                    <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20 / trang</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 / trang</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 / trang</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="min_stock" class="form-control" placeholder="Tồn kho từ" value="{{ request('min_stock') }}">
            </div>
            <div class="col-md-2">
                <input type="number" name="max_stock" class="form-control" placeholder="Tồn kho đến" value="{{ request('max_stock') }}">
            </div>
        </div>
    </form>

    <div class="mb-3">
        <button class="btn btn-danger" id="bulk-delete-btn">Xoá các mục đã chọn</button>
    </div>

    <div id="variants-table">
        @include('product_variants._variants_table')
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function showNotification(message, type = 'success') {
        var container = $('#notification-container');
        if (container.length === 0) {
            // If the container doesn't exist, create it and append it to the body
            container = $('<div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
            $('body').append(container);
        }

        // Create the toast element
        var toast = $(`
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);

        // Append the toast to the container and show it
        container.append(toast);
        var toastInstance = new bootstrap.Toast(toast[0]);
        toastInstance.show();
    }
    
    function load_variants(url) {
        $.ajax({
            url: url,
            success: function(data) {
                $('#variants-table').html(data);
            }
        });
    }
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        var url = "{{ route('product-variants.index') }}" + "?" + $(this).serialize();
        load_variants(url);
    });

    $(document).on('change', 'select[name=per_page]', function() {
        $('#filter-form').submit();
    });

    $(document).on('click', '.pagination a', function(event) {
        event.preventDefault();
        load_variants($(this).attr('href'));
    });
    
    $(document).on('change', '#select-all', function() {
        $('.variant-checkbox').prop('checked', $(this).prop('checked'));
    });

    $(document).on('click', '#bulk-delete-btn', function() {
        var selected = [];
        $('.variant-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length > 0) {
            if (confirm('Bạn có chắc muốn xoá các biến thể đã chọn?')) {
                $.ajax({
                    url: "{{ route('product-variants.bulk-delete') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: selected
                    },
                    success: function(response) {
                        showNotification(response.success, 'success');
                        // Reload the current page
                        var currentPageUrl = $('.pagination .active .page-link').attr('href') || "{{ route('product-variants.index') }}";
                        load_variants(currentPageUrl);
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = 'Đã có lỗi xảy ra. Vui lòng thử lại.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showNotification(errorMessage, 'danger');
                    }
                });
            }
        } else {
            showNotification('Vui lòng chọn ít nhất một biến thể để xoá.', 'warning');
        }
    });
});
</script>
@endpush
