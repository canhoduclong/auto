@if($transfer->order && auth()->user()?->isAdmin())
    <details class="mt-2 text-start">
        <summary class="text-danger small" style="cursor: pointer;">
            <i class="bi bi-trash me-1"></i>Admin: Xóa cứng đơn sai số liệu
        </summary>
        <form method="POST" action="{{ route('orders.admin-delete', $transfer->order) }}"
              class="border border-danger rounded p-2 mt-2"
              data-order-code="{{ $transfer->order->code ?: '#'.$transfer->order_id }}"
              onsubmit="return window.confirm('Xóa vĩnh viễn toàn bộ đơn ' + this.dataset.orderCode + '? Đây là thao tác loại bỏ dữ liệu sai, chỉ dành cho admin. Không thể khôi phục đơn qua giao diện.');">
            @csrf
            @method('DELETE')
            <div class="small text-danger mb-2">
                Xóa toàn bộ đơn {{ $transfer->order->code ?: '#'.$transfer->order_id }} cùng các phiếu điều chuyển của đơn. Chỉ admin được dùng để loại bỏ số liệu sai sót; không dùng chỉ vì đơn đã cũ.
            </div>
            <label class="d-block small">
                Lý do xóa
                <textarea name="reason" class="form-control form-control-sm mt-1" rows="2"
                          required minlength="5" maxlength="500"
                          placeholder="Nhập lý do xóa (ít nhất 5 ký tự)"></textarea>
            </label>
            <button type="submit" class="btn btn-danger btn-sm mt-2">
                <i class="bi bi-trash me-1"></i>Xóa vĩnh viễn đơn này
            </button>
        </form>
    </details>
@endif
