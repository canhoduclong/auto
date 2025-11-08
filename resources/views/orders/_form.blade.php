<div class="mb-3">
    <label for="customer_id" class="form-label">Khách hàng</label>
    <select name="customer_id" id="customer_id" class="form-control" required>
        <option value="">-- Chọn khách hàng --</option>
        @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ (isset($order) && $order->customer_id == $customer->id) ? 'selected' : '' }}>{{ $customer->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label for="user_id" class="form-label">Nhân viên phụ trách</label>
    <select name="user_id" id="user_id" class="form-control" required>
        <option value="">-- Chọn nhân viên --</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ (isset($order) && $order->user_id == $user->id) ? 'selected' : '' }}>{{ $user->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label for="status" class="form-label">Trạng thái</label>
    <select name="status" id="status" class="form-control">
        <option value="pending" {{ (isset($order) && $order->status == 'pending') ? 'selected' : '' }}>Chờ xử lý</option>
        <option value="processing" {{ (isset($order) && $order->status == 'processing') ? 'selected' : '' }}>Đang xử lý</option>
        <option value="completed" {{ (isset($order) && $order->status == 'completed') ? 'selected' : '' }}>Hoàn thành</option>
        <option value="cancelled" {{ (isset($order) && $order->status == 'cancelled') ? 'selected' : '' }}>Đã hủy</option>
    </select>
</div>
<div class="mb-3">
    <label for="total" class="form-label">Tổng tiền đơn hàng</label>
    <input type="number" step="0.01" name="total" id="total" class="form-control" value="{{ old('total', $order->total ?? '') }}" required>
</div>
<div class="mb-3">
    <label for="amount_paid" class="form-label">Số tiền đã thanh toán</label>
    <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" value="{{ old('amount_paid', $order->amount_paid ?? 0) }}">
</div>
<div class="mb-3">
    <label for="amount_due" class="form-label">Số tiền còn thiếu</label>
    <input type="number" step="0.01" name="amount_due" id="amount_due" class="form-control" value="{{ old('amount_due', $order->amount_due ?? (($order->total ?? 0) - ($order->amount_paid ?? 0))) }}">
</div>
<div class="mb-3">
    <label for="payment_method" class="form-label">Phương thức thanh toán</label>
    <select name="payment_method" id="payment_method" class="form-control">
        <option value="">-- Chọn --</option>
        <option value="cod" {{ old('payment_method', $order->payment_method ?? '')=='cod'?'selected':'' }}>Tiền mặt/COD</option>
        <option value="bank" {{ old('payment_method', $order->payment_method ?? '')=='bank'?'selected':'' }}>Chuyển khoản</option>
        <option value="other" {{ old('payment_method', $order->payment_method ?? '')=='other'?'selected':'' }}>Khác</option>
    </select>
</div>
<div class="mb-3">
    <label for="payment_status" class="form-label">Trạng thái thanh toán</label>
    <select name="payment_status" id="payment_status" class="form-control">
        <option value="unpaid" {{ old('payment_status', $order->payment_status ?? '')=='unpaid'?'selected':'' }}>Chưa thanh toán</option>
        <option value="partial" {{ old('payment_status', $order->payment_status ?? '')=='partial'?'selected':'' }}>Thanh toán một phần</option>
        <option value="paid" {{ old('payment_status', $order->payment_status ?? '')=='paid'?'selected':'' }}>Đã thanh toán đủ</option>
    </select>
</div>
