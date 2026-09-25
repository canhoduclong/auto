<!-- Modal chọn khách hàng, sẽ được include vào create.blade.php -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerModalLabel">{{ __('customers.popup.modal_title') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.actions.close') }}"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <input type="text" id="searchName" class="form-control" placeholder="{{ __('customers.popup.search_name') }}">
          </div>
          <div class="col-md-4">
            <input type="text" id="searchPhone" class="form-control" placeholder="{{ __('customers.popup.search_phone') }}">
          </div>
          <div class="col-md-4">
            <input type="text" id="searchEmail" class="form-control" placeholder="{{ __('customers.popup.search_email') }}">
          </div>
        </div>
        <div id="customerList">
          <!-- Danh sách khách hàng sẽ được load ajax -->
        </div>
        <button class="btn btn-success mt-3" id="btnShowAddCustomer">{{ __('customers.popup.add_new') }}</button>
        <div id="addCustomerForm" class="mt-3" style="display:none;">
          <h6>{{ __('customers.popup.add_new_title') }}</h6>
          <form id="formAddCustomer">
            <div class="row">
              <div class="col-md-4 mb-2">
                <input type="text" name="name" class="form-control" placeholder="{{ __('customers.popup.customer_name_placeholder') }}" required>
              </div>
              <div class="col-md-4 mb-2">
                <input type="text" name="phone" class="form-control" placeholder="{{ __('customers.popup.customer_phone_placeholder') }}">
              </div>
              <div class="col-md-4 mb-2">
                <input type="email" name="email" class="form-control" placeholder="{{ __('customers.popup.email') }}">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save') }}</button>
            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelAddCustomer">{{ __('common.actions.cancel') }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
