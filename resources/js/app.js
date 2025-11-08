// --- Giao dịch: chọn đơn hàng, hiển thị tổng tiền, thanh toán toàn bộ ---
    let orderSelect = document.getElementById('order_id_select');
    let orderTotalBox = document.getElementById('order_total_box');
    let orderTotalText = document.getElementById('order_total_text');
    let payFullOrder = document.getElementById('pay_full_order');
    let amountInput = document.getElementById('amount_input');
    let currentOrderTotal = 0;
    if (orderSelect) {
        orderSelect.addEventListener('change', function() {
            // Reset thông tin khách hàng khi chọn đơn hàng
            let customerIdInput = document.getElementById('customer_id');
            let customerNameInput = document.getElementById('customer_name');
            if (customerIdInput) customerIdInput.value = '';
            if (customerNameInput) customerNameInput.value = '';
            let orderId = orderSelect.value;
            if (orderId) {
                fetch('/orders/ajax/total?order_id=' + orderId)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            currentOrderTotal = data.total;
                            orderTotalText.textContent = data.total.toLocaleString('vi-VN') + ' đ';
                            orderTotalBox.style.display = '';
                        } else {
                            orderTotalBox.style.display = 'none';
                        }
                    });
            } else {
                orderTotalBox.style.display = 'none';
            }
        });
    }
    // --- Thanh toán đủ cho form giao dịch đơn hàng ---
    if (payFullOrder && amountInput) {
        // Lấy số tiền còn lại từ biến blade nếu có
        let remain = 0;
        if (window.remainAmount !== undefined) {
            remain = window.remainAmount;
        } else if (amountInput.hasAttribute('max')) {
            remain = parseInt(amountInput.getAttribute('max')) || 0;
        }
        payFullOrder.addEventListener('change', function() {
            if (payFullOrder.checked && remain > 0) {
                amountInput.value = remain;
                amountInput.dispatchEvent(new Event('input', { bubbles: true }));
                amountInput.readOnly = true;
            } else {
                amountInput.value = '';
                amountInput.readOnly = false;
            }
        });
    }
    // Nếu chọn lại đơn hàng khác, bỏ check "thanh toán toàn bộ"
    if (orderSelect && payFullOrder) {
        orderSelect.addEventListener('change', function() {
            payFullOrder.checked = false;
            amountInput.readOnly = false;
        });
    }
import './bootstrap';

document.addEventListener("DOMContentLoaded", function () {
    // --- Nhân bản biến thể ở trang product-variants index ---
    document.querySelectorAll('.clone-variant-index').forEach(function(btn) {
        btn.addEventListener('click', function() {
            let tr = btn.closest('tr');
            if (!tr) return;
            let id = btn.getAttribute('data-variant-id');
            // Tạo form xác nhận gửi về route nhân bản
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = `/product-variants/${id}/duplicate`;
            form.innerHTML = `
                <input type='hidden' name='_token' value='${document.querySelector('meta[name=csrf-token]')?.content || ''}'>
                <button type='submit' class='btn btn-success btn-sm'>Xác nhận nhân bản</button>
                <button type='button' class='btn btn-secondary btn-sm cancel-clone-variant'>Hủy</button>
            `;
            let newRow = document.createElement('tr');
            let td = document.createElement('td');
            td.colSpan = tr.children.length;
            td.appendChild(form);
            newRow.appendChild(td);
            tr.parentNode.insertBefore(newRow, tr.nextSibling);
            // Hủy
            form.querySelector('.cancel-clone-variant').onclick = function() { newRow.remove(); };
        });
    });

    // --- Sửa nhanh biến thể ở trang product-variants index ---
    document.querySelectorAll('.quick-edit-variant-index').forEach(function(btn) {
        btn.addEventListener('click', function() {
            let tr = btn.closest('tr');
            if (!tr) return;
            // Nếu đã có form thì không thêm nữa
            if (tr.nextSibling && tr.nextSibling.classList && tr.nextSibling.classList.contains('quick-edit-row')) return;
            let id = btn.getAttribute('data-variant-id');
            let tds = tr.querySelectorAll('td');
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = `/product-variants/${id}`;
            let price = tds[7].innerText.trim().replace(/[^0-9]/g, '');
            form.innerHTML = `
                <input type='hidden' name='_token' value='${document.querySelector('meta[name=csrf-token]')?.content || ''}'>
                <input type='hidden' name='_method' value='PUT'>
                <div style="display: inline-block; margin-right: 10px;">
                    <label>SKU</label>
                    <input type='text' name='sku' value='${tds[2].innerText.trim()}' class='form-control' style='width:100px;'>
                </div>
                <div style="display: inline-block; margin-right: 10px;">
                    <label>Size</label>
                    <input type='text' name='size' value='${tds[4].innerText.trim()}' class='form-control' style='width:80px;'>
                </div>
                <div style="display: inline-block; margin-right: 10px;">
                    <label>Quality</label>
                    <input type='text' name='quality' value='${tds[5].innerText.trim()}' class='form-control' style='width:80px;'>
                </div>
                <div style="display: inline-block; margin-right: 10px;">
                    <label>Production Date</label>
                    <input type='date' name='production_date' value='${tds[6].innerText.trim()}' class='form-control' style='width:120px;'>
                </div>
                <div style="display: inline-block; margin-right: 10px;">
                    <label>Price</label>
                    <input type='text' name='price' value='${price}' class='form-control format-number' style='width:100px;'>
                </div>
                <div style="display: inline-block; margin-right: 10px;">
                    <label>Stock</label>
                    <input type='number' name='stock' value='${tds[8].innerText.trim()}' class='form-control' style='width:80px;'>
                </div>
                <button type='submit' class='btn btn-primary btn-sm'>Lưu</button>
                <button type='button' class='btn btn-secondary btn-sm cancel-quick-edit-variant'>Hủy</button>
            `;
            let newRow = document.createElement('tr');
            newRow.classList.add('quick-edit-row');
            let td = document.createElement('td');
            td.colSpan = tr.children.length;
            td.appendChild(form);
            newRow.appendChild(td);
            tr.parentNode.insertBefore(newRow, tr.nextSibling);
            // Hủy
            form.querySelector('.cancel-quick-edit-variant').onclick = function() { newRow.remove(); };
        });
    });
    // --- Nhân bản biến thể ---
    document.querySelectorAll('.clone-variant').forEach(function(btn) {
        btn.addEventListener('click', function() {
            let tr = btn.closest('tr');
            if (!tr) return;
            let clone = tr.cloneNode(true);
            // Xóa id variant để tạo mới
            clone.removeAttribute('data-variant-id');
            // Đổi tất cả name="variants[ID]..." thành name="variants[new_x]..."
            let newId = 'new_' + Math.floor(Math.random()*1000000);
            clone.querySelectorAll('[name]').forEach(function(input) {
                input.name = input.name.replace(/variants\[[^\]]+\]/, 'variants['+newId+']');
                if (input.type !== 'hidden') input.value = '';
                if (input.type === 'date') input.value = '';
            });
            // Xóa ảnh preview nếu có
            let img = clone.querySelector('.variant-image-preview img');
            if (img) img.remove();
            // Reset media id
            let mediaInput = clone.querySelector('input[name$="[media_id]"]');
            if (mediaInput) mediaInput.value = '';
            // Thêm vào cuối tbody
            tr.parentNode.appendChild(clone);
        });
    });

    // --- Sửa nhanh biến thể ---
    document.querySelectorAll('.quick-edit-variant').forEach(function(btn) {
        btn.addEventListener('click', function() {
            let tr = btn.closest('tr');
            if (!tr) return;
            // Enable tất cả input trong dòng này
            tr.querySelectorAll('input,select').forEach(function(input) {
                input.removeAttribute('readonly');
                input.removeAttribute('disabled');
            });
            // Focus vào ô đầu tiên
            let firstInput = tr.querySelector('input,select');
            if (firstInput) firstInput.focus();
        });
    });
    // Hàm format số có dấu phân cách
    function formatNumber(value) {
        if (!value) return "";
        return value.replace(/\D/g, "")  // loại bỏ ký tự không phải số
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ","); // thêm dấu ,
    }

    // Sử dụng event delegation cho các input có class "format-number"
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('format-number')) {
            let input = e.target;
            let cursor = input.selectionStart;
            let beforeLength = input.value.length;

            input.value = formatNumber(input.value);

            let afterLength = input.value.length;
            input.selectionEnd = cursor + (afterLength - beforeLength);
        }
    });

    // Khi submit form: bỏ dấu phẩy để lưu DB
    document.addEventListener('submit', function(e) {
        if (e.target.tagName === 'FORM') {
            e.target.querySelectorAll('.format-number').forEach(function(input) {
                input.value = input.value.replace(/,/g, "");
            });
        }
    });
    // --- Popup chọn khách hàng ---
    function loadCustomerList(params = {}) {
        let url = '/customers/popup/search?'+new URLSearchParams(params).toString();
        fetch(url)
            .then(res => res.json())
            .then(data => {
                document.getElementById('customerList').innerHTML = data.html;
            });
    }

    // Khi mở modal thì load danh sách
    let customerModal = document.getElementById('customerModal');
    if (customerModal) {
        let customerListLoaded = false;
        customerModal.addEventListener('show.bs.modal', function () {
            if (!customerListLoaded) {
                loadCustomerList();
                customerListLoaded = true;
            }
            document.getElementById('addCustomerForm').style.display = 'none';
        });
    }

    // Tìm kiếm (reset lại trạng thái đã load để cho phép load lại khi tìm kiếm)
    ['searchName','searchPhone','searchEmail'].forEach(function(id) {
        let el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                loadCustomerList({
                    name: document.getElementById('searchName').value,
                    phone: document.getElementById('searchPhone').value,
                    email: document.getElementById('searchEmail').value
                });
                if (customerModal) customerListLoaded = true;
            });
        }
    });

    // Phân trang ajax
    document.addEventListener('click', function(e) {
        if (e.target.closest('#customerList .pagination a')) {
            e.preventDefault();
            let url = e.target.getAttribute('href');
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('customerList').innerHTML = data.html;
                });
        }
    });

    // Chọn khách hàng
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-select-customer')) {
            let id = e.target.getAttribute('data-id');
            let name = e.target.getAttribute('data-name');
            document.getElementById('customer_id').value = id;
            document.getElementById('customer_name').value = name;
            // Reset chọn đơn hàng và tổng tiền
            if (orderSelect) orderSelect.value = '';
            if (orderTotalBox) orderTotalBox.style.display = 'none';
            if (payFullOrder) payFullOrder.checked = false;
            if (amountInput) {
                amountInput.value = '';
                amountInput.readOnly = false;
            }
            let modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
            modal.hide();
        }
    });

    // Hiện form thêm mới
    let btnShowAdd = document.getElementById('btnShowAddCustomer');
    if (btnShowAdd) {
        btnShowAdd.addEventListener('click', function() {
            document.getElementById('addCustomerForm').style.display = '';
        });
    }
    // Ẩn form thêm mới
    let btnCancelAdd = document.getElementById('btnCancelAddCustomer');
    if (btnCancelAdd) {
        btnCancelAdd.addEventListener('click', function() {
            document.getElementById('addCustomerForm').style.display = 'none';
        });
    }
    // Submit thêm khách hàng
    let formAdd = document.getElementById('formAddCustomer');
    if (formAdd) {
        formAdd.addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(formAdd);
            fetch('/customers/popup/store', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]')?.value
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('customer_id').value = data.customer.id;
                    document.getElementById('customer_name').value = data.customer.name;
                    let modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                    modal.hide();
                }
            });
        });
    }
});
