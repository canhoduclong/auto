@if(empty($readOnly) && auth()->user()?->isAdmin() && $slip->status === 'finalized')
    <form method="POST" action="{{ route($dispatchRoutePrefix.'.destroy', $slip) }}"
          data-slip-code="{{ $slip->code }}"
          onsubmit="const reason = window.prompt('Lý do xóa phiếu đã xuất (ít nhất 5 ký tự):'); if (!reason || reason.trim().length < 5 || reason.trim().length > 500) return false; this.elements.reason.value = reason.trim(); return window.confirm('Xóa phiếu tổng ' + this.dataset.slipCode + '? Đơn hàng, điều chuyển và số liệu tồn kho được giữ nguyên.');">
        @csrf
        @method('DELETE')
        <input type="hidden" name="reason" value="">
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Admin: Xóa phiếu đã xuất</button>
    </form>
@endif
