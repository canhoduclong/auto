<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal-{{ $customer->id }}" tabindex="-1" aria-labelledby="editCustomerModalLabel-{{ $customer->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel-{{ $customer->id }}">Sửa thông tin khách hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('my_customer.update', $customer) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name-{{ $customer->id }}" class="form-label">Tên</label>
                        <input type="text" class="form-control" id="name-{{ $customer->id }}" name="name" value="{{ $customer->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="email-{{ $customer->id }}" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email-{{ $customer->id }}" name="email" value="{{ $customer->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone-{{ $customer->id }}" class="form-label">Điện thoại</label>
                        <input type="text" class="form-control" id="phone-{{ $customer->id }}" name="phone" value="{{ $customer->phone }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>
