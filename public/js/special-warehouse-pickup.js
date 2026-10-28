(() => {
    const escape = value => { const node = document.createElement('span'); node.textContent = value ?? ''; return node.innerHTML; };
    const money = value => Number(value).toLocaleString('vi-VN', {maximumFractionDigits: 2}) + ' đ';
    const token = () => crypto.randomUUID ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (Number(c) ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> Number(c) / 4).toString(16));
    async function post(url, data) {
        const response = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}, body: JSON.stringify(data)});
        const body = await response.json();
        if (!response.ok) throw new Error(Object.values(body.errors || {}).flat().join(' ') || body.message || 'Không thể hoàn tất đơn.');
        return body;
    }
    window.SpecialWarehousePickup = {
        mount(button, getCart) {
            if (!button) return {refresh() {}};
            let sequence = 0;
            let timer;
            let eligibility;
            async function check() {
                const current = ++sequence;
                button.hidden = true;
                const cart = getCart();
                if (!cart.customer_id || !cart.items.length) return;
                try {
                    const data = await post(window.specialPickupEndpoints.options, {customer_id: cart.customer_id, variant_ids: cart.items.map(item => item.variant_id)});
                    if (current !== sequence) return;
                    eligibility = data;
                    button.hidden = !data.eligible;
                } catch { if (current === sequence) button.hidden = true; }
            }
            function refresh() { sequence++; button.hidden = true; clearTimeout(timer); timer = setTimeout(check, 250); }
            button.addEventListener('click', async () => {
                const cart = getCart();
                button.disabled = true;
                try {
                    eligibility = await post(window.specialPickupEndpoints.options, {customer_id: cart.customer_id, variant_ids: cart.items.map(item => item.variant_id)});
                    if (!eligibility.eligible) throw new Error('Khách hoặc sản phẩm đã tắt Hàng đặc biệt.');
                    open(cart, eligibility);
                } catch (error) { window.alert(error.message); }
                finally { button.disabled = false; }
            });
            function open(cart, options) {
                const dialog = document.createElement('dialog');
                dialog.style.cssText = 'width:min(960px,95vw);max-height:92vh;overflow:auto;border:1px solid #cbd5e1;border-radius:12px;padding:24px;';
                const creationToken = token();
                const variants = new Map((options.variants || []).map(variant => [Number(variant.id), variant]));
                dialog.innerHTML = `<form><h4>Tạo và hoàn tất tại kho</h4>
                    <p>Khách đã nhận hàng tại kho; chờ kế toán xác nhận doanh thu và cập nhật thanh toán. Shipper ghi nhận trên đơn: <strong>${escape(options.shipper?.name || 'Chưa cấu hình shipper mặc định')}</strong>.</p>
                    <p class="small text-muted">Kiểm tra số lượng, khối lượng và giá thực bán. Không thu phí vận chuyển; giá và tổng tiền bên dưới là số liệu để hoàn tất.</p>
                    <div class="row g-3"><div class="col-md-6"><label class="form-label">Kho giao hàng<select name="warehouse_id" class="form-select" required><option value="">Chọn kho</option>${options.warehouses.map(w => `<option value="${w.id}">${escape(w.name)}</option>`).join('')}</select></label></div>
                    </div>
                    <label class="form-label mt-3">Nguồn hàng<select name="stock_source" class="form-select"><option value="existing">Xuất từ tồn kho hiện có</option><option value="new_receipt">Hàng vừa nhập thực tế: lập phiếu nhập rồi xuất bán</option></select></label>
                    <label class="form-label d-block" data-receipt hidden>Nguồn nhập thực tế<input name="receipt_source" class="form-control" maxlength="500" placeholder="Ví dụ: lông thu hồi từ sản xuất ngày hôm nay"></label>
                    <div class="table-responsive mt-3"><table class="table table-sm"><thead><tr><th>Sản phẩm</th><th>SL thực giao</th><th>Tổng kg thực tế</th><th>Đơn giá bán</th><th data-receipt hidden>Giá vốn nhập</th><th>Thành tiền</th></tr></thead><tbody>${cart.items.map(item => {
                        const variant = variants.get(Number(item.variant_id));
                        return `<tr data-id="${item.variant_id}" data-priced-by-kg="${variant?.is_priced_by_kg ? '1' : '0'}"><td>${escape(variant?.name || item.name || '#' + item.variant_id)}</td><td><input class="form-control form-control-sm" name="quantity" type="number" min="1" max="100000" step="1" required value="${Number(item.quantity)}"></td><td><input class="form-control form-control-sm" name="actual_weight" type="number" min="0.001" step="0.001" required value="${Number(item.actual_weight).toFixed(3)}"></td><td><input class="form-control form-control-sm" name="unit_price" type="number" min="0.01" step="0.01" required value="${Number(item.unit_price).toFixed(2)}"></td><td data-receipt hidden><input class="form-control form-control-sm" name="unit_cost" type="number" min="0" step="0.01"></td><td data-line-total></td></tr>`;
                    }).join('')}</tbody></table></div>
                    <div class="fw-bold">Giá trị đơn chờ kế toán xác nhận <span data-total></span>${cart.charge_vat ? ` (đã gồm VAT ${Number(cart.vat_percent)}%)` : ''}</div>
                    <label class="form-label d-block">Ghi chú<textarea name="note" class="form-control" maxlength="2000">${escape(cart.note || '')}</textarea></label>
                    <label class="d-block"><input type="checkbox" name="goods_received" required> Tôi xác nhận khách đã nhận hàng tại kho</label>
                    <p class="small text-muted mt-2">Xác nhận chỉ hoàn tất giao hàng và chứng từ kho. Kế toán sẽ xác nhận doanh thu, cập nhật tiền thu sau.</p>
                    <div data-error class="text-danger my-2" role="alert"></div>
                    <button type="submit" class="btn btn-success" ${!options.shipper ? 'disabled' : ''}>Tạo và hoàn tất tại kho</button> <button type="button" data-close class="btn btn-light">Đóng</button>
                </form>`;
                document.body.append(dialog);
                const form = dialog.querySelector('form');
                const rows = Array.from(form.querySelectorAll('tbody tr'));
                function recalculate() {
                    let subtotal = 0;
                    for (const row of rows) {
                        const basis = Number(row.querySelector(`[name="${row.dataset.pricedByKg === '1' ? 'actual_weight' : 'quantity'}"]`).value);
                        const total = basis * Number(row.querySelector('[name="unit_price"]').value);
                        row.querySelector('[data-line-total]').textContent = money(total);
                        subtotal += total;
                    }
                    const total = Math.round((subtotal + (cart.charge_vat ? Math.round(subtotal * Number(cart.vat_percent || 0)) / 100 : 0)) * 100) / 100;
                    form.querySelector('[data-total]').textContent = money(total);
                }
                rows.forEach(row => row.addEventListener('input', recalculate));
                form.elements.namedItem('stock_source').addEventListener('change', event => {
                    const imported = event.target.value === 'new_receipt';
                    form.querySelectorAll('[data-receipt]').forEach(el => el.hidden = !imported);
                    form.elements.namedItem('receipt_source').required = imported;
                    form.querySelectorAll('[name="unit_cost"]').forEach(el => el.required = imported);
                });
                let pending = false;
                const close = () => { if (!pending) { dialog.close(); dialog.remove(); } };
                form.querySelector('[data-close]').addEventListener('click', close);
                dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
                form.addEventListener('submit', async event => {
                    event.preventDefault();
                    if (pending) return;
                    pending = true;
                    const submit = form.querySelector('[type="submit"]'); submit.disabled = true;
                    const field = name => form.elements.namedItem(name).value;
                    const data = {
                        creation_token: creationToken, customer_id: cart.customer_id, warehouse_id: Number(field('warehouse_id')),
                        stock_source: field('stock_source'), receipt_source: field('receipt_source'),
                        charge_vat: Boolean(cart.charge_vat), vat_percent: Number(cart.vat_percent || 0), note: field('note'), goods_received: true,
                        items: rows.map(row => ({variant_id: Number(row.dataset.id), quantity: Number(row.querySelector('[name="quantity"]').value), actual_weight: Number(row.querySelector('[name="actual_weight"]').value), unit_price: Number(row.querySelector('[name="unit_price"]').value), ...(field('stock_source') === 'new_receipt' ? {unit_cost: Number(row.querySelector('[name="unit_cost"]').value)} : {})})),
                    };
                    try { const result = await post(window.specialPickupEndpoints.store, data); window.location.assign(result.order.url); }
                    catch (error) { form.querySelector('[data-error]').textContent = error.message; pending = false; submit.disabled = false; }
                });
                recalculate(); dialog.showModal();
            }
            return {refresh};
        }
    };
})();
