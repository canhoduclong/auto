<!-- Modal chọn khách hàng, sẽ được include vào create.blade.php -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerModalLabel">Chọn khách hàng</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <input type="text" id="searchName" class="form-control" placeholder="Tìm theo tên">
          </div>
          <div class="col-md-4">
            <input type="text" id="searchPhone" class="form-control" placeholder="Tìm theo SĐT">
          </div>
          <div class="col-md-4">
            <input type="text" id="searchEmail" class="form-control" placeholder="Tìm theo Email">
          </div>
        </div>
        <div id="customerList">
          <!-- Danh sách khách hàng sẽ được load ajax -->
        </div>
        <button class="btn btn-success mt-3" id="btnShowAddCustomer">+ Thêm khách hàng mới</button>
        <div id="addCustomerForm" class="mt-3" style="display:none;">
          <h6>Thêm khách hàng mới</h6>
          <form id="formAddCustomer">
            <div class="row">
              <div class="col-md-4 mb-2">
                <input type="text" name="name" class="form-control" placeholder="Tên khách hàng" required>
              </div>
              <div class="col-md-4 mb-2">
                <input type="text" name="phone" class="form-control" placeholder="Số điện thoại">
              </div>
              <div class="col-md-4 mb-2">
                <input type="email" name="email" class="form-control" placeholder="Email">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelAddCustomer">Hủy</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
