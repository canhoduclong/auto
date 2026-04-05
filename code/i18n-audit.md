# I18N Audit Report

Generated: 2026-03-17 15:06:44

## Blade hard-coded candidates
resources/views/dashboard/manager.blade.php:5:    <p>{{ __('dashboard.manager.welcome', ['name' => auth()->user()->name]) }}</p>
resources/views/dashboard/staff.blade.php:5:    <p>{{ __('dashboard.staff.welcome', ['name' => auth()->user()->name]) }}</p>
resources/views/dashboard/admin.blade.php:16:                <div class="text-muted">{{ __('dashboard.admin.welcome', ['name' => $user->name]) }}</div>
resources/views/dashboard/admin.blade.php:157:                                                <a href="{{ route('orders.show', $order) }}">{{ $order->code ?: ('#' . $order->id) }}</a>
resources/views/dashboard/admin.blade.php:159:                                            <td>{{ $order->customer->name ?? __('dashboard.admin.na') }}</td>
resources/views/dashboard/admin.blade.php:160:                                            <td>{{ $order->user->name ?? __('dashboard.admin.na') }}</td>
resources/views/dashboard/admin.blade.php:161:                                            <td>{{ number_format((float) $order->total, 0, ',', '.') }} đ</td>
resources/views/dashboard/admin.blade.php:162:                                            <td><span class="badge bg-secondary">{{ $order->status }}</span></td>
resources/views/dashboard/admin.blade.php:163:                                            <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
resources/views/dashboard/admin.blade.php:195:                                            <td>{{ $item->product->name ?? ('#' . $item->product_id) }}</td>
resources/views/dashboard/admin.blade.php:196:                                            <td>{{ number_format((int) $item->sold_qty) }}</td>
resources/views/dashboard/admin.blade.php:197:                                            <td>{{ number_format((float) $item->sold_amount, 0, ',', '.') }} đ</td>
resources/views/dashboard/admin.blade.php:218:                                <span class="text-muted">{{ $status->status }}</span>
resources/views/dashboard/admin.blade.php:219:                                <span class="badge bg-primary">{{ number_format($status->total) }}</span>
resources/views/dashboard/default.blade.php:5:    <p>{{ __('dashboard.user.welcome', ['name' => auth()->user()->name]) }}</p>
resources/views/auth/login.blade.php:35:                        placeholder="you@example.com"
resources/views/auth/login.blade.php:45:                        {{-- <a href="#" class="small">Quên mật khẩu?</a> --}}
resources/views/auth/register.blade.php:35:                        placeholder="Tên của bạn"
resources/views/auth/register.blade.php:49:                        placeholder="you@example.com"
resources/views/teams/edit.blade.php:5:    <h2>Cập nhật team</h2>
resources/views/teams/edit.blade.php:12:            <label class="form-label">Tên team</label>
resources/views/teams/edit.blade.php:17:            <label class="form-label">Mã team</label>
resources/views/teams/edit.blade.php:22:            <label class="form-label">Ghi chú</label>
resources/views/teams/edit.blade.php:23:            <textarea name="note" class="form-control" rows="3">{{ old('note', $team->note) }}</textarea>
resources/views/teams/edit.blade.php:26:        <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/teams/edit.blade.php:27:        <a href="{{ route('teams.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/teams/index.blade.php:6:        <h2 class="mb-0">Danh sách Team</h2>
resources/views/teams/index.blade.php:21:                <th>ID</th>
resources/views/teams/index.blade.php:22:                <th>Tên team</th>
resources/views/teams/index.blade.php:23:                <th>Mã team</th>
resources/views/teams/index.blade.php:24:                <th>Số user</th>
resources/views/teams/index.blade.php:25:                <th>Ghi chú</th>
resources/views/teams/index.blade.php:26:                <th>Hành động</th>
resources/views/teams/index.blade.php:32:                    <td>{{ $team->id }}</td>
resources/views/teams/index.blade.php:33:                    <td>{{ $team->name }}</td>
resources/views/teams/index.blade.php:34:                    <td>{{ $team->code ?? '-' }}</td>
resources/views/teams/index.blade.php:35:                    <td>{{ $team->users_count }}</td>
resources/views/teams/index.blade.php:36:                    <td>{{ $team->note ?? '-' }}</td>
resources/views/teams/index.blade.php:38:                        <a href="{{ route('teams.edit', $team) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/teams/index.blade.php:42:                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xóa team này?')">Xóa</button>
resources/views/teams/index.blade.php:48:                    <td colspan="6" class="text-center">Chưa có team.</td>
resources/views/teams/create.blade.php:5:    <h2>Tạo team mới</h2>
resources/views/teams/create.blade.php:11:            <label class="form-label">Tên team</label>
resources/views/teams/create.blade.php:16:            <label class="form-label">Mã team</label>
resources/views/teams/create.blade.php:21:            <label class="form-label">Ghi chú</label>
resources/views/teams/create.blade.php:25:        <button type="submit" class="btn btn-success">Lưu</button>
resources/views/teams/create.blade.php:26:        <a href="{{ route('teams.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/customers/addresses/list.blade.php:5:    <h2>Danh sách tất cả địa chỉ khách hàng</h2>
resources/views/customers/addresses/list.blade.php:9:            <label class="form-label">Tìm (tên khách / SĐT / địa chỉ)</label>
resources/views/customers/addresses/list.blade.php:10:            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nhập tên, sđt, đường, căn...">
resources/views/customers/addresses/list.blade.php:14:            <label class="form-label">Tên khách hàng</label>
resources/views/customers/addresses/list.blade.php:15:            <input type="text" name="customer_name" class="form-control" value="{{ request('customer_name') }}" placeholder="Tên khách">
resources/views/customers/addresses/list.blade.php:19:            <label class="form-label">Số điện thoại</label>
resources/views/customers/addresses/list.blade.php:20:            <input type="text" name="customer_phone" class="form-control" value="{{ request('customer_phone') }}" placeholder="SĐT">
resources/views/customers/addresses/list.blade.php:24:            <label class="form-label">Thành phố</label>
resources/views/customers/addresses/list.blade.php:34:            <label class="form-label">Số / trang</label>
resources/views/customers/addresses/list.blade.php:43:            <button class="btn btn-primary">Lọc</button>
resources/views/customers/addresses/list.blade.php:44:            <a href="{{ route('customers.addresses.list') }}" class="btn btn-outline-secondary">Reset</a>
resources/views/customers/addresses/list.blade.php:52:                <th>ID</th> 
resources/views/customers/addresses/list.blade.php:53:                <th>Khách hàng</th>
resources/views/customers/addresses/list.blade.php:54:                <th>Phone</th>
resources/views/customers/addresses/list.blade.php:55:                <th>Dự án / Zone / Block</th>
resources/views/customers/addresses/list.blade.php:56:                <th>Tầng / Căn</th>
resources/views/customers/addresses/list.blade.php:58:                <th>Mặc định</th>
resources/views/customers/addresses/list.blade.php:59:                <th>Thao tác</th>
resources/views/customers/addresses/list.blade.php:65:                    <td>{{ $addr->id }}</td>
resources/views/customers/addresses/list.blade.php:73:                    <td>{{ optional($addr->customer)->phone ?? '-' }}</td>
resources/views/customers/addresses/list.blade.php:79:                    <td>{{ $addr->floor ? 'T' . $addr->floor . ' / ' : '' }}{{ $addr->unit_number }}</td>
resources/views/customers/addresses/list.blade.php:88:                            <span class="badge bg-success">Mặc định</span>
resources/views/customers/addresses/list.blade.php:93:                        <a href="{{ route('customers.addresses.edit', [$addr->customer_id, $addr->id]) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/customers/addresses/list.blade.php:98:                            <button class="btn btn-sm btn-danger">Xóa</button>
resources/views/customers/addresses/list.blade.php:103:                <tr><td colspan="9" class="text-center">Không tìm thấy địa chỉ nào</td></tr>
resources/views/customers/addresses/list.blade.php:110:        <div>Hiển thị {{ $addresses->firstItem() ?? 0 }} - {{ $addresses->lastItem() ?? 0 }} / {{ $addresses->total() }} địa chỉ</div>
resources/views/customers/addresses/list.blade.php:111:        <div>{{ $addresses->links() }}</div>
resources/views/customers/addresses/edit.blade.php:5:    <h2>Sửa địa chỉ của {{ $customer->name }}</h2> 
resources/views/customers/addresses/edit.blade.php:8:        <strong>Mã KH:</strong> {{ $customer->id }} <br>
resources/views/customers/addresses/edit.blade.php:9:        <strong>Tên:</strong> {{ $customer->name }} <br>
resources/views/customers/addresses/edit.blade.php:10:        <strong>Email:</strong> {{ $customer->email ?? '—' }} <br>
resources/views/customers/addresses/edit.blade.php:11:        <strong>Số điện thoại:</strong> {{ $customer->phone ?? '—' }}
resources/views/customers/addresses/edit.blade.php:18:        <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/customers/addresses/edit.blade.php:19:        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-secondary">Hủy</a>
resources/views/customers/addresses/form.blade.php:4:        <label>Số nhà</label>
resources/views/customers/addresses/form.blade.php:8:        <label>Số nhà tạm</label>
resources/views/customers/addresses/form.blade.php:18:        <label>Tên dự án</label>
resources/views/customers/addresses/form.blade.php:22:        <label>Block</label>
resources/views/customers/addresses/form.blade.php:28:        <label>Tầng</label>
resources/views/customers/addresses/form.blade.php:32:        <label>Số căn</label>
resources/views/customers/addresses/form.blade.php:39:<h5>Thông tin chung</h5>
resources/views/customers/addresses/form.blade.php:42:        <label>Tên đường</label>
resources/views/customers/addresses/form.blade.php:46:        <label>Phường/Xã</label>
resources/views/customers/addresses/form.blade.php:50:        <label>Quận/Huyện</label>
resources/views/customers/addresses/form.blade.php:54:        <label>Tỉnh/Thành phố</label>
resources/views/customers/addresses/form.blade.php:65:        <label>Ghi chú</label>
resources/views/customers/addresses/form.blade.php:66:        <textarea name="note" class="form-control">{{ old('note', $address->note ?? '') }}</textarea>
resources/views/customers/addresses/index.blade.php:16:            <p class="mb-1"><strong>Mã KH:</strong> <span class="badge bg-secondary">{{ $customer->id }}</span></p>
resources/views/customers/addresses/index.blade.php:17:            <p class="mb-1"><strong>Email:</strong> {{ $customer->email ?? '—' }}</p>
resources/views/customers/addresses/index.blade.php:18:            <p class="mb-1"><strong>SĐT:</strong> {{ $customer->phone ?? '—' }}</p>
resources/views/customers/addresses/index.blade.php:24:        <h5 class="mb-0">Danh sách địa chỉ</h5>
resources/views/customers/addresses/index.blade.php:40:                        <th>Số nhà/Căn hộ</th>
resources/views/customers/addresses/index.blade.php:41:                        <th>Chi tiết</th> 
resources/views/customers/addresses/index.blade.php:42:                        <th class="text-center">Mặc định</th>
resources/views/customers/addresses/index.blade.php:43:                        <th class="text-end">Hành động</th>
resources/views/customers/addresses/index.blade.php:99:        <td colspan="5" class="text-center text-muted py-4">Chưa có địa chỉ nào</td>
resources/views/customers/addresses/create.blade.php:5:    <h2>Thêm địa chỉ cho {{ $customer->name }}</h2>
resources/views/customers/addresses/create.blade.php:10:        <button type="submit" class="btn btn-success">Lưu</button>
resources/views/customers/addresses/create.blade.php:11:        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-secondary">Hủy</a>
resources/views/customers/edit.blade.php:5:    <h2>Sửa thông tin: {{ $customer->name }}</h2>
resources/views/customers/edit.blade.php:12:            <button class="btn btn-primary">Cập nhật</button>
resources/views/customers/edit.blade.php:13:            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/customers/_form.blade.php:3:        <label class="form-label">Họ & tên</label>
resources/views/customers/_form.blade.php:9:        <label class="form-label">Phone</label>
resources/views/customers/_form.blade.php:15:        <label class="form-label">Email</label>
resources/views/customers/_form.blade.php:21:        <label class="form-label">Website</label>
resources/views/customers/_form.blade.php:28:        <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->addresses->where('is_default', 1)->first()->note ?? '') }}</textarea>
resources/views/customers/_form.blade.php:33:        <label class="form-label">Giới tính</label>
resources/views/customers/_form.blade.php:36:            <option value="male" {{ old('gender', $customer->gender ?? '') === 'male' ? 'selected' : '' }}>Nam</option>
resources/views/customers/_form.blade.php:37:            <option value="female" {{ old('gender', $customer->gender ?? '') === 'female' ? 'selected' : '' }}>Nữ</option>
resources/views/customers/_form.blade.php:38:            <option value="other" {{ old('gender', $customer->gender ?? '') === 'other' ? 'selected' : '' }}>Khác</option>
resources/views/customers/_form.blade.php:44:        <label class="form-label">Ngày sinh</label>
resources/views/customers/_form.blade.php:50:        <label class="form-label">Loại khách hàng</label>
resources/views/customers/_form.blade.php:63:        <label class="form-label">Ghi chú</label>
resources/views/customers/_form.blade.php:64:        <textarea name="note" class="form-control" rows="3">{{ old('note', $customer->note ?? '') }}</textarea>
resources/views/customers/_form.blade.php:68:        <label class="form-label">Giờ giao hàng</label>
resources/views/customers/_form.blade.php:69:        <input type="text" name="delivery_time" class="form-control" value="{{ old('delivery_time', $customer->delivery_time ?? '') }}" placeholder="VD: 8h-10h sáng">
resources/views/customers/_form.blade.php:75:            <option value="0" {{ old('foam_box_required', $customer->foam_box_required ?? 0)==0?'selected':'' }}>Không</option>
resources/views/customers/_form.blade.php:76:            <option value="1" {{ old('foam_box_required', $customer->foam_box_required ?? 0)==1?'selected':'' }}>Có (+70.000đ)</option>
resources/views/customers/_form.blade.php:81:        <label class="form-label">Có giao chành xe?</label>
resources/views/customers/_form.blade.php:83:            <option value="0" {{ old('use_truck_station', $customer->use_truck_station ?? 0)==0?'selected':'' }}>Không</option>
resources/views/customers/_form.blade.php:84:            <option value="1" {{ old('use_truck_station', $customer->use_truck_station ?? 0)==1?'selected':'' }}>Có</option>
resources/views/customers/_form.blade.php:95:            <label class="form-label">Giờ nhận hàng tại chành</label>
resources/views/customers/_form.blade.php:100:            <label class="form-label">Giờ trả hàng tại chành</label>
resources/views/customers/_form.blade.php:110:            <label class="form-label">Số điện thoại chành xe</label>
resources/views/customers/_form.blade.php:115:            <label class="form-label">Phí chành xe (VNĐ)</label>
resources/views/customers/_form.blade.php:120:            <label class="form-label">Hóa đơn chứng từ (link/ảnh)</label>
resources/views/customers/popup_list.blade.php:5:            <th>Tên</th>
resources/views/customers/popup_list.blade.php:6:            <th>SĐT</th>
resources/views/customers/popup_list.blade.php:7:            <th>Email</th>
resources/views/customers/popup_list.blade.php:14:            <td>{{ $customer->name }}</td>
resources/views/customers/popup_list.blade.php:15:            <td>{{ $customer->phone }}</td>
resources/views/customers/popup_list.blade.php:16:            <td>{{ $customer->email }}</td>
resources/views/customers/popup_list.blade.php:18:                <button class="btn btn-sm btn-primary btn-select-customer" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}">Chọn</button>
resources/views/customers/popup_list.blade.php:28:<p>Không tìm thấy khách hàng phù hợp.</p>
resources/views/customers/import.blade.php:4:    <h2>Import khách hàng từ Excel</h2>
resources/views/customers/import.blade.php:6:        <b>Hướng dẫn file Excel import:</b><br>
resources/views/customers/import.blade.php:7:        - Hàng đầu tiên phải có các cột: <b>name</b> (bắt buộc), <b>phone</b> (bắt buộc, dạng chuỗi), <b>address</b> (bắt buộc), <b>email</b> (email, không bắt buộc), <b>gender</b> (male/female/other), <b>dob</b> (YYYY-MM-DD), <b>customer_type_id</b> (ID loại KH), <b>note</b> (ghi chú).<br>
resources/views/customers/import.blade.php:8:        - Cột <b>address</b> không được để trống.<br>
resources/views/customers/import.blade.php:9:        - Cột <b>phone</b> nên để dạng chuỗi, ví dụ: '0123456789'.<br>
resources/views/customers/import.blade.php:11:        <a href="/sample/customer_import_sample.xlsx" target="_blank">Tải file mẫu</a>
resources/views/customers/import.blade.php:16:        <button class="btn btn-primary">Import</button>
resources/views/customers/import.blade.php:23:            <strong>Các dòng lỗi khi import:</strong>
resources/views/customers/import.blade.php:27:                        <b>Dòng:</b> {{ $err['row'] }} | <b>Cột:</b> {{ $err['attribute'] }}<br>
resources/views/customers/import.blade.php:28:                        <b>Lỗi:</b> {{ implode('; ', $err['errors']) }}<br>
resources/views/customers/import.blade.php:29:                        <b>Giá trị:</b> {{ json_encode($err['values']) }}
resources/views/customers/import.blade.php:37:            <strong>Kết quả import từng dòng:</strong>
resources/views/customers/import.blade.php:45:                            <b>Lỗi:</b> {{ $rec['error'] ?? '' }}
resources/views/customers/import.blade.php:52:    <a href="{{ route('customers.index') }}" class="btn btn-secondary mt-2">Quay lại danh sách khách hàng</a>
resources/views/customers/index.blade.php:5:    <h2>Danh sách khách hàng</h2>
resources/views/customers/index.blade.php:10:            <button type="submit" class="btn btn-danger" onclick="return confirm('Xác nhận xóa các khách hàng đã chọn?')">Xóa đã chọn</button>
resources/views/customers/index.blade.php:16:                <button class="btn btn-warning">Import Excel</button>
resources/views/customers/index.blade.php:18:            <a href="{{ route('customers.export') }}" class="btn btn-info">Export Excel</a>
resources/views/customers/index.blade.php:25:                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm tên / SĐT / Email">
resources/views/customers/index.blade.php:57:                <button class="btn btn-primary">Lọc</button>
resources/views/customers/index.blade.php:58:                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Reset</a>
resources/views/customers/index.blade.php:75:                <th>Họ & Tên</th>
resources/views/customers/index.blade.php:76:                <th>Phone</th>
resources/views/customers/index.blade.php:77:                <th>Email</th>
resources/views/customers/index.blade.php:78:                <th>Loại</th> 
resources/views/customers/index.blade.php:79:                <th>Nhân viên</th>
resources/views/customers/index.blade.php:81:                <th>Hành động</th>
resources/views/customers/index.blade.php:87:                    <td><input type="checkbox" class="row-check" value="{{ $customer->id }}"></td>
resources/views/customers/index.blade.php:88:                    <td>{{ $customer->id }}</td>
resources/views/customers/index.blade.php:90:                        {{ $customer->name }} <br>
resources/views/customers/index.blade.php:100:                    <td>{{ $customer->phone }}</td>
resources/views/customers/index.blade.php:101:                    <td>{{ $customer->email }}</td>
resources/views/customers/index.blade.php:102:                    <td>{{ optional($customer->type)->name ?? '-' }}</td>
resources/views/customers/index.blade.php:103:                    <td>{{ optional($customer->assignedTo)->name ?? '-' }}</td>
resources/views/customers/index.blade.php:120:                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/customers/index.blade.php:121:                        <a href="{{ route('customers.addresses.index', $customer->id) }}" class="btn btn-sm btn-info">Địa chỉ</a>
resources/views/customers/index.blade.php:122:                        <a href="{{ route('customers.report', $customer) }}" class="btn btn-sm btn-primary">Báo cáo</a>
resources/views/customers/index.blade.php:125:                            <button class="btn btn-sm btn-danger">Xóa</button>
resources/views/customers/index.blade.php:131:                    <td colspan="9" class="text-center">Chưa có khách hàng</td>
resources/views/customers/report.blade.php:7:            <h4 class="mb-1">Báo cáo khách hàng</h4>
resources/views/customers/report.blade.php:15:        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Quay lại danh sách</a>
resources/views/customers/report.blade.php:22:                    <label for="from_date" class="form-label">Từ ngày</label>
resources/views/customers/report.blade.php:30:                    <label for="per_page" class="form-label">Số dòng / trang</label>
resources/views/customers/report.blade.php:38:                    <button type="submit" class="btn btn-primary">Lọc báo cáo</button>
resources/views/customers/report.blade.php:39:                    <a href="{{ route('customers.report', $customer) }}" class="btn btn-outline-secondary">Reset</a>
resources/views/customers/report.blade.php:49:                    <div class="text-muted mb-1">Tổng giá trị đơn hàng</div>
resources/views/customers/report.blade.php:57:                    <div class="text-muted mb-1">Tổng đã thanh toán</div>
resources/views/customers/report.blade.php:65:                    <div class="text-muted mb-1">Tổng công nợ</div>
resources/views/customers/report.blade.php:76:                    <th>ID</th>
resources/views/customers/report.blade.php:77:                    <th>Mã đơn</th>
resources/views/customers/report.blade.php:78:                    <th>Ngày tạo</th>
resources/views/customers/report.blade.php:79:                    <th>Nhân viên</th>
resources/views/customers/report.blade.php:80:                    <th>Tổng tiền</th>
resources/views/customers/report.blade.php:82:                    <th>Công nợ</th>
resources/views/customers/report.blade.php:83:                    <th>Trạng thái đơn</th>
resources/views/customers/report.blade.php:84:                    <th>Trạng thái thanh toán</th>
resources/views/customers/report.blade.php:85:                    <th>Thao tác</th>
resources/views/customers/report.blade.php:96:                        <td>{{ $order->id }}</td>
resources/views/customers/report.blade.php:97:                        <td>{{ $order->code }}</td>
resources/views/customers/report.blade.php:98:                        <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
resources/views/customers/report.blade.php:99:                        <td>{{ optional($order->user)->name ?? '-' }}</td>
resources/views/customers/report.blade.php:100:                        <td>{{ number_format($order->total, 0, ',', '.') }} đ</td>
resources/views/customers/report.blade.php:103:                        <td>{{ $order->status }}</td>
resources/views/customers/report.blade.php:108:                                <span class="badge bg-warning text-dark">Thanh toán một phần</span>
resources/views/customers/report.blade.php:110:                                <span class="badge bg-danger">Chưa thanh toán</span>
resources/views/customers/report.blade.php:114:                            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">Xem đơn</a>
resources/views/customers/report.blade.php:119:                        <td colspan="10" class="text-center text-muted">Khách hàng này chưa có đơn hàng.</td>
resources/views/customers/popup_select.blade.php:6:        <h5 class="modal-title" id="customerModalLabel">Chọn khách hàng</h5>
resources/views/customers/popup_select.blade.php:7:        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
resources/views/customers/popup_select.blade.php:12:            <input type="text" id="searchName" class="form-control" placeholder="Tìm theo tên">
resources/views/customers/popup_select.blade.php:15:            <input type="text" id="searchPhone" class="form-control" placeholder="Tìm theo SĐT">
resources/views/customers/popup_select.blade.php:18:            <input type="text" id="searchEmail" class="form-control" placeholder="Tìm theo Email">
resources/views/customers/popup_select.blade.php:26:          <h6>Thêm khách hàng mới</h6>
resources/views/customers/popup_select.blade.php:30:                <input type="text" name="name" class="form-control" placeholder="Tên khách hàng" required>
resources/views/customers/popup_select.blade.php:33:                <input type="text" name="phone" class="form-control" placeholder="Số điện thoại">
resources/views/customers/popup_select.blade.php:36:                <input type="email" name="email" class="form-control" placeholder="Email">
resources/views/customers/popup_select.blade.php:39:            <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
resources/views/customers/popup_select.blade.php:40:            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelAddCustomer">Hủy</button>
resources/views/customers/types/edit.blade.php:5:    <h2>Chỉnh sửa loại khách hàng</h2>
resources/views/customers/types/edit.blade.php:13:        <button type="submit" class="btn btn-success">Cập nhật</button>
resources/views/customers/types/edit.blade.php:14:        <a href="{{ route('customertype.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/customers/types/form.blade.php:2:    <label>Tên loại *</label>
resources/views/customers/types/form.blade.php:7:    <label>Mô tả</label>
resources/views/customers/types/form.blade.php:8:    <textarea name="description" class="form-control">{{ old('description', $type->description ?? '') }}</textarea>
resources/views/customers/types/form.blade.php:13:        <label>Số đơn tối thiểu</label>
resources/views/customers/types/form.blade.php:17:        <label>Tổng chi tiêu tối thiểu</label>
resources/views/customers/types/form.blade.php:21:        <label>Thời hạn (ngày)</label>
resources/views/customers/types/form.blade.php:28:        <label>Chiết khấu (%)</label>
resources/views/customers/types/form.blade.php:32:        <label>Freeship</label>
resources/views/customers/types/form.blade.php:34:            <option value="0" {{ old('free_shipping', $type->free_shipping ?? 0) == 0 ? 'selected' : '' }}>Không</option>
resources/views/customers/types/form.blade.php:35:            <option value="1" {{ old('free_shipping', $type->free_shipping ?? 0) == 1 ? 'selected' : '' }}>Có</option>
resources/views/customers/types/form.blade.php:39:        <label>Mức ưu tiên</label>
resources/views/customers/types/index.blade.php:5:    <h2>Quản lý loại khách hàng</h2>
resources/views/customers/types/index.blade.php:15:                <th>Tên loại</th>
resources/views/customers/types/index.blade.php:16:                <th>Chiết khấu (%)</th>
resources/views/customers/types/index.blade.php:17:                <th>Freeship</th>
resources/views/customers/types/index.blade.php:20:                <th width="150">Thao tác</th>
resources/views/customers/types/index.blade.php:26:                    <td>{{ $type->name }}</td>
resources/views/customers/types/index.blade.php:27:                    <td>{{ $type->discount_rate }}%</td>
resources/views/customers/types/index.blade.php:28:                    <td>{{ $type->free_shipping ? 'Có' : 'Không' }}</td>
resources/views/customers/types/index.blade.php:29:                    <td>{{ $type->priority_level }}</td>
resources/views/customers/types/index.blade.php:35:                        <a href="{{ route('customertype.edit', $type) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/customers/types/index.blade.php:39:                                onclick="return confirm('Bạn chắc chắn muốn xóa?')">Xóa</button>
resources/views/customers/types/create.blade.php:5:    <h2>Thêm loại khách hàng</h2>
resources/views/customers/types/create.blade.php:12:        <button type="submit" class="btn btn-success">Lưu</button>
resources/views/customers/types/create.blade.php:13:        <a href="{{ route('customertype.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/customers/create.blade.php:5:    <h2>Thêm khách hàng mới</h2>
resources/views/customers/create.blade.php:27:            <button class="btn btn-success">Lưu</button>
resources/views/customers/create.blade.php:28:            <a href="{{ route('customers.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/customers/popup_pagination.blade.php:8:                <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a></li>
resources/views/customers/popup_pagination.blade.php:32:                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a></li>
resources/views/orders/list.blade.php:4:            <th>Sản phẩm</th>
resources/views/orders/list.blade.php:5:            <th>Biến thể</th>
resources/views/orders/list.blade.php:6:            <th>Giá</th>
resources/views/orders/list.blade.php:7:            <th>Xóa</th>
resources/views/orders/list.blade.php:13:            <td>{{ $variant->product->name ?? '' }}</td>
resources/views/orders/list.blade.php:14:            <td>{{ $variant->variant_name ?? ($variant->sku ?? $variant->id) }}</td>
resources/views/orders/list.blade.php:15:            <td>{{ number_format($variant->price ?? 0, 0, ',', '.') }} đ</td>
resources/views/orders/list.blade.php:16:            <td><button type="button" class="btn btn-danger btn-sm remove-variant-btn" data-variant-id="{{ $variant->id }}">X</button></td>
resources/views/orders/list.blade.php:21:<div class="text-end fw-bold">Tổng tiền: <span id="list-total">{{ number_format($total, 0, ',', '.') }}</span> đ</div>
resources/views/orders/_order_items.blade.php:1:<h5>Danh sách sản phẩm trong đơn hàng</h5>
resources/views/orders/_order_items.blade.php:5:            <th>Sản phẩm</th>
resources/views/orders/_order_items.blade.php:6:            <th>Biến thể</th>
resources/views/orders/_order_items.blade.php:7:            <th>Số lượng</th>
resources/views/orders/_order_items.blade.php:9:            <th>Thành tiền</th>
resources/views/orders/_order_items.blade.php:15:            <td>{{ $item->product_variant->product->name ?? '' }}</td>
resources/views/orders/_order_items.blade.php:16:            <td>{{ $item->product_variant->variant_name ?? '' }}</td>
resources/views/orders/_order_items.blade.php:17:            <td>{{ $item->quantity }}</td>
resources/views/orders/_order_items.blade.php:18:            <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
resources/views/orders/_order_items.blade.php:19:            <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
resources/views/orders/_variant_search_results.blade.php:2:    <p class="text-center p-3">No products found.</p>
resources/views/orders/_variant_search_results.blade.php:9:            <label for="per-page-select" class="form-label me-2 mb-0">Per Page:</label>
resources/views/orders/_variant_search_results.blade.php:11:                <option value="5" {{ $variants->perPage() == 5 ? 'selected' : '' }}>5</option>
resources/views/orders/_variant_search_results.blade.php:12:                <option value="10" {{ $variants->perPage() == 10 ? 'selected' : '' }}>10</option>
resources/views/orders/_variant_search_results.blade.php:13:                <option value="25" {{ $variants->perPage() == 25 ? 'selected' : '' }}>25</option>
resources/views/orders/_variant_search_results.blade.php:14:                <option value="50" {{ $variants->perPage() == 50 ? 'selected' : '' }}>50</option>
resources/views/orders/_variant_search_results.blade.php:32:                        <h6 class="my-0">{{ $variant->product->name }}</h6>
resources/views/orders/_variant_search_results.blade.php:33:                        <small class="text-muted">SKU: {{ $variant->sku }} | Price: {{ number_format($variant->latestPriceRule?->price ?? 0) }} | Stock: {{ $variant->stock }}</small>
resources/views/orders/edit.blade.php:4:    <h4>Cập nhật đơn hàng #{{ $order->code }}</h4>
resources/views/orders/edit.blade.php:9:            <label for="customer_id" class="form-label">Khách hàng</label>
resources/views/orders/edit.blade.php:12:                    <option value="{{ $customer->id }}" {{ $order->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
resources/views/orders/edit.blade.php:17:            <label for="user_id" class="form-label">Nhân viên phụ trách</label>
resources/views/orders/edit.blade.php:20:                    <option value="{{ $user->id }}" {{ $order->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
resources/views/orders/edit.blade.php:25:            <label for="status" class="form-label">Trạng thái</label>
resources/views/orders/edit.blade.php:33:        <label>Chọn biến thể:</label>
resources/views/orders/edit.blade.php:37:                <option value="{{ $variant->id }}">{{ $variant->product->name ?? '' }} - {{ $variant->variant_name ?? ($variant->sku ?? $variant->id) }}</option>
resources/views/orders/edit.blade.php:40:        <button type="button" id="edit-add-variant" class="btn btn-success btn-sm">Thêm biến thể</button>
resources/views/orders/edit.blade.php:44:        <strong>Tổng tiền: <span id="edit-order-total">0</span> đ</strong>
resources/views/orders/edit.blade.php:46:    <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/orders/edit.blade.php:47:    <a href="{{ route('orders.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/orders/create_new.blade.php:5:    <h1>Tạo đơn hàng mới</h1>
resources/views/orders/create_new.blade.php:29:                <h5 class="mb-0">Danh sách sản phẩm trong đơn</h5>
resources/views/orders/create_new.blade.php:35:                            <th style="width: 10%;">Hình ảnh</th>
resources/views/orders/create_new.blade.php:36:                            <th style="width: 30%;">Sản phẩm</th>
resources/views/orders/create_new.blade.php:37:                            <th>SKU</th>
resources/views/orders/create_new.blade.php:38:                            <th>Giá</th>
resources/views/orders/create_new.blade.php:39:                            <th>Số lượng</th>
resources/views/orders/create_new.blade.php:40:                            <th>Thành tiền</th>
resources/views/orders/create_new.blade.php:62:                            <td>{{ $variant->sku }}</td>
resources/views/orders/create_new.blade.php:63:                            <td class="price" data-price="{{ $variant->latestPriceRule?->price ?? 0 }}">{{ number_format($variant->latestPriceRule?->price ?? 0) }}</td>
resources/views/orders/create_new.blade.php:67:                            <td class="row-total">{{ number_format($variant->latestPriceRule?->price ?? 0) }}</td>
resources/views/orders/create_new.blade.php:76:                <h5>Tổng cộng: <span id="cart-total">{{ number_format($variant->latestPriceRule?->price ?? 0) }}</span></h5>
resources/views/orders/create_new.blade.php:83:                <h5 class="mb-0">Thêm sản phẩm</h5>
resources/views/orders/create_new.blade.php:87:                    <input type="text" id="variant-search" class="form-control" placeholder="Tìm sản phẩm theo tên hoặc SKU...">
resources/views/orders/create_new.blade.php:88:                    <button class="btn btn-outline-secondary" type="button" id="variant-search-button">Tìm</button>
resources/views/orders/create_new.blade.php:97:                <h5 class="mb-0">Thông tin khách hàng</h5>
resources/views/orders/create_new.blade.php:101:                    <h6>Chọn khách hàng</h6>
resources/views/orders/create_new.blade.php:103:                        <input type="text" id="customer-search" class="form-control" placeholder="Tìm kiếm khách hàng...">
resources/views/orders/create_new.blade.php:104:                        <button class="btn btn-outline-secondary" type="button" id="customer-search-button">Tìm</button>
resources/views/orders/create_new.blade.php:114:            <button type="submit" class="btn btn-primary btn-lg">Tạo Đơn Hàng</button>
resources/views/orders/create_new.blade.php:132:        customerListContainer.html('<div class="text-center my-3"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
resources/views/orders/create_new.blade.php:137:            error: function() { customerListContainer.html('<p class="text-danger text-center my-3">Lỗi khi tải danh sách khách hàng.</p>'); }
resources/views/orders/create_new.blade.php:183:        variantSearchResults.html('<div class="text-center my-3"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
resources/views/orders/create_new.blade.php:191:            error: function() { variantSearchResults.html('<p class="text-danger text-center my-3">Lỗi khi tải sản phẩm.</p>'); }
resources/views/orders/create_new.blade.php:213:                error: function() { variantSearchResults.html('<p class="text-danger text-center my-3">Lỗi khi tải sản phẩm.</p>'); }
resources/views/orders/_form.blade.php:2:    <label for="customer_id" class="form-label">Khách hàng</label>
resources/views/orders/_form.blade.php:6:            <option value="{{ $customer->id }}" {{ (isset($order) && $order->customer_id == $customer->id) ? 'selected' : '' }}>{{ $customer->name }}</option>
resources/views/orders/_form.blade.php:11:    <label for="user_id" class="form-label">Nhân viên phụ trách</label>
resources/views/orders/_form.blade.php:15:            <option value="{{ $user->id }}" {{ (isset($order) && $order->user_id == $user->id) ? 'selected' : '' }}>{{ $user->name }}</option>
resources/views/orders/_form.blade.php:20:    <label for="status" class="form-label">Trạng thái</label>
resources/views/orders/_form.blade.php:22:        <option value="pending" {{ (isset($order) && $order->status == 'pending') ? 'selected' : '' }}>Chờ xử lý</option>
resources/views/orders/_form.blade.php:23:        <option value="processing" {{ (isset($order) && $order->status == 'processing') ? 'selected' : '' }}>Đang xử lý</option>
resources/views/orders/_form.blade.php:24:        <option value="completed" {{ (isset($order) && $order->status == 'completed') ? 'selected' : '' }}>Hoàn thành</option>
resources/views/orders/_form.blade.php:25:        <option value="cancelled" {{ (isset($order) && $order->status == 'cancelled') ? 'selected' : '' }}>Đã hủy</option>
resources/views/orders/_form.blade.php:29:    <label for="total" class="form-label">Tổng tiền đơn hàng</label>
resources/views/orders/_form.blade.php:33:    <label for="amount_paid" class="form-label">Số tiền đã thanh toán</label>
resources/views/orders/_form.blade.php:37:    <label for="amount_due" class="form-label">Số tiền còn thiếu</label>
resources/views/orders/_form.blade.php:41:    <label for="payment_method" class="form-label">Phương thức thanh toán</label>
resources/views/orders/_form.blade.php:44:        <option value="cod" {{ old('payment_method', $order->payment_method ?? '')=='cod'?'selected':'' }}>Tiền mặt/COD</option>
resources/views/orders/_form.blade.php:45:        <option value="bank" {{ old('payment_method', $order->payment_method ?? '')=='bank'?'selected':'' }}>Chuyển khoản</option>
resources/views/orders/_form.blade.php:46:        <option value="other" {{ old('payment_method', $order->payment_method ?? '')=='other'?'selected':'' }}>Khác</option>
resources/views/orders/_form.blade.php:50:    <label for="payment_status" class="form-label">Trạng thái thanh toán</label>
resources/views/orders/_form.blade.php:52:        <option value="unpaid" {{ old('payment_status', $order->payment_status ?? '')=='unpaid'?'selected':'' }}>Chưa thanh toán</option>
resources/views/orders/_form.blade.php:53:        <option value="partial" {{ old('payment_status', $order->payment_status ?? '')=='partial'?'selected':'' }}>Thanh toán một phần</option>
resources/views/orders/_form.blade.php:54:        <option value="paid" {{ old('payment_status', $order->payment_status ?? '')=='paid'?'selected':'' }}>Đã thanh toán đủ</option>
resources/views/orders/list_variant.blade.php:4:            <th>Sản phẩm</th>
resources/views/orders/list_variant.blade.php:5:            <th>Biến thể</th>
resources/views/orders/list_variant.blade.php:6:            <th>Số lượng</th>
resources/views/orders/list_variant.blade.php:8:            <th>Thành tiền</th>
resources/views/orders/list_variant.blade.php:9:            <th>Xóa</th>
resources/views/orders/list_variant.blade.php:15:            <td>{{ $item->variant->product->name ?? '' }}</td>
resources/views/orders/list_variant.blade.php:16:            <td>{{ $item->variant->variant_name ?? ($item->variant->sku ?? $item->variant->id) }}</td>
resources/views/orders/list_variant.blade.php:17:            <td>{{ $item->quantity }}</td>
resources/views/orders/list_variant.blade.php:18:            <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
resources/views/orders/list_variant.blade.php:19:            <td>{{ number_format($item->quantity * $item->price, 0, ',', '.') }} đ</td>
resources/views/orders/list_variant.blade.php:20:            <td><button type="button" class="btn btn-danger btn-sm remove-variant-btn" data-variant-id="{{ $item->variant_id }}">X</button></td>
resources/views/orders/list_variant.blade.php:25:<div class="text-end fw-bold">Tổng tiền: <span id="edit-list-total">{{ number_format($total, 0, ',', '.') }}</span> đ</div>
resources/views/orders/index.blade.php:6:        <h4 class="mb-0">Quản lý đơn hàng</h4>
resources/views/orders/index.blade.php:8:            <a href="{{ route('approval-workflows.create') }}" class="btn btn-outline-primary">Tạo quy trình</a>
resources/views/orders/index.blade.php:15:            <h5 class="card-title mb-0">Bộ lọc</h5>
resources/views/orders/index.blade.php:22:                            <label for="customer_name" class="form-label">Tên khách hàng</label>
resources/views/orders/index.blade.php:28:                            <label for="phone_number" class="form-label">Số điện thoại</label>
resources/views/orders/index.blade.php:34:                            <label for="user_id" class="form-label">Người cập nhật</label>
resources/views/orders/index.blade.php:36:                                <option value="">Tất cả</option>
resources/views/orders/index.blade.php:38:                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
resources/views/orders/index.blade.php:45:                            <label for="team_id" class="form-label">Team</label>
resources/views/orders/index.blade.php:47:                                <option value="">Tất cả</option>
resources/views/orders/index.blade.php:49:                                    <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
resources/views/orders/index.blade.php:56:                            <label for="payment_status" class="form-label">Trạng thái thanh toán</label>
resources/views/orders/index.blade.php:58:                                <option value="">Tất cả</option>
resources/views/orders/index.blade.php:60:                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
resources/views/orders/index.blade.php:61:                                <option value="partially_paid" {{ request('payment_status') == 'partially_paid' ? 'selected' : '' }}>Thanh toán một phần</option>
resources/views/orders/index.blade.php:67:                            <label for="status" class="form-label">Trạng thái đơn hàng</label>
resources/views/orders/index.blade.php:69:                                <option value="">Tất cả</option>
resources/views/orders/index.blade.php:78:                            <label for="from_date" class="form-label">Từ ngày</label>
resources/views/orders/index.blade.php:91:                            <label class="form-check-label" for="my_pending_approval">Chỉ hiện đơn chờ tôi duyệt</label>
resources/views/orders/index.blade.php:95:                <button type="submit" class="btn btn-primary">Lọc</button>
resources/views/orders/index.blade.php:96:                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Xóa bộ lọc</a>
resources/views/orders/index.blade.php:103:            <h5 class="card-title mb-0">Thống kê</h5>
resources/views/orders/index.blade.php:108:                    <p><strong>Tổng tiền hóa đơn:</strong> {{ number_format($totalInvoiceAmount, 0, ',', '.') }} đ</p>
resources/views/orders/index.blade.php:111:                    <p><strong>Tổng đã thanh toán:</strong> {{ number_format($totalPaidAmount, 0, ',', '.') }} đ</p>
resources/views/orders/index.blade.php:114:                    <p><strong>Tổng còn nợ:</strong> {{ number_format($totalOutstandingAmount, 0, ',', '.') }} đ</p>
resources/views/orders/index.blade.php:129:                    <th>ID</th>
resources/views/orders/index.blade.php:130:                    <th>Mã đơn</th>
resources/views/orders/index.blade.php:131:                    <th>Khách hàng</th>
resources/views/orders/index.blade.php:132:                    <th>Nhân viên</th>
resources/views/orders/index.blade.php:133:                    <th>Tổng tiền</th>
resources/views/orders/index.blade.php:134:                    <th>Trạng thái</th>
resources/views/orders/index.blade.php:135:                    <th>Trạng thái thanh toán</th>
resources/views/orders/index.blade.php:137:                    <th>Ngày tạo</th>
resources/views/orders/index.blade.php:138:                    <th>Duyệt</th>
resources/views/orders/index.blade.php:139:                    <th>Thao tác</th>
resources/views/orders/index.blade.php:140:                    <th>QR Code</th>
resources/views/orders/index.blade.php:146:                    <td>{{ $order->id }}</td>
resources/views/orders/index.blade.php:147:                    <td>{{ $order->code }}</td>
resources/views/orders/index.blade.php:148:                    <td>{{ $order->customer->name ?? '' }}</td>
resources/views/orders/index.blade.php:149:                    <td>{{ $order->user->name ?? '' }}</td>
resources/views/orders/index.blade.php:150:                    <td>{{ number_format($order->total, 0, ',', '.') }} đ</td>
resources/views/orders/index.blade.php:162:                            <span class="badge bg-warning text-dark">Thanh toán một phần</span>
resources/views/orders/index.blade.php:164:                            <span class="badge bg-danger">Chưa thanh toán</span>
resources/views/orders/index.blade.php:167:                    <td>{{ $order->created_at }}</td>
resources/views/orders/index.blade.php:188:                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
resources/views/orders/index.blade.php:193:                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn từ chối đơn này?')">Reject</button>
resources/views/orders/index.blade.php:196:                                <small class="text-muted d-block">Không đúng vai trò duyệt</small>
resources/views/orders/index.blade.php:199:                            <small class="text-muted">Không có bước duyệt chờ</small>
resources/views/orders/index.blade.php:208:                            <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm ms-2">Thanh toán</a>
resources/views/orders/index.blade.php:214:                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">xem</a>
resources/views/orders/index.blade.php:216:                            <a href="{{ route('order-returns.create', ['order_id' => $order->id]) }}" class="btn btn-warning btn-sm">Tra hang</a>
resources/views/orders/index.blade.php:219:                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-info btn-sm">Sửa</a>
resources/views/orders/index.blade.php:224:                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xóa đơn hàng này?')">Xóa</button>
resources/views/orders/index.blade.php:227:                            <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm">Thanh toán</a>
resources/views/orders/list_table.blade.php:4:            <th>ID</th>
resources/views/orders/list_table.blade.php:5:            <th>Mã đơn</th>
resources/views/orders/list_table.blade.php:6:            <th>Khách hàng</th>
resources/views/orders/list_table.blade.php:7:            <th>Nhân viên</th>
resources/views/orders/list_table.blade.php:8:            <th>Tổng tiền</th>
resources/views/orders/list_table.blade.php:9:            <th>Trạng thái</th>
resources/views/orders/list_table.blade.php:10:            <th>Trạng thái thanh toán</th>
resources/views/orders/list_table.blade.php:12:            <th>Ngày tạo</th>
resources/views/orders/list_table.blade.php:13:            <th>Thao tác</th>
resources/views/orders/list_table.blade.php:19:            <td>{{ $order->id }}</td>
resources/views/orders/list_table.blade.php:20:            <td>{{ $order->code }}</td>
resources/views/orders/list_table.blade.php:21:            <td>{{ $order->customer->name ?? '' }}</td>
resources/views/orders/list_table.blade.php:22:            <td>{{ $order->user->name ?? '' }}</td>
resources/views/orders/list_table.blade.php:23:            <td>{{ number_format($order->total, 0, ',', '.') }} đ</td>
resources/views/orders/list_table.blade.php:35:                    <span class="badge bg-warning text-dark">Thanh toán một phần</span>
resources/views/orders/list_table.blade.php:37:                    <span class="badge bg-danger">Chưa thanh toán</span>
resources/views/orders/list_table.blade.php:40:            <td>{{ $order->created_at }}</td>
resources/views/orders/list_table.blade.php:47:                    <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm ms-2">Thanh toán</a>
resources/views/orders/list_table.blade.php:54:                    <button class="btn btn-info btn-sm edit-order" data-id="{{ $order->id }}">Sửa</button>
resources/views/orders/list_table.blade.php:56:                <button class="btn btn-danger btn-sm delete-order" data-id="{{ $order->id }}">Xóa</button>
resources/views/orders/list_table.blade.php:58:                    <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success btn-sm">Thanh toán</a>
resources/views/orders/_order_items_edit.blade.php:1:<h5>Sản phẩm trong đơn hàng</h5>
resources/views/orders/_order_items_edit.blade.php:5:            <th>Sản phẩm</th>
resources/views/orders/_order_items_edit.blade.php:6:            <th>Biến thể</th>
resources/views/orders/_order_items_edit.blade.php:7:            <th>Số lượng</th>
resources/views/orders/_order_items_edit.blade.php:9:            <th>Thành tiền</th>
resources/views/orders/_order_items_edit.blade.php:19:                        <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
resources/views/orders/_order_items_edit.blade.php:31:                            <option value="{{ $variant->id }}" {{ $item->product_variant_id == $variant->id ? 'selected' : '' }}>{{ $variant->variant_name }}</option>
resources/views/orders/_order_items_edit.blade.php:36:            <td><input type="number" name="items[{{ $idx }}][quantity]" class="form-control" value="{{ $item->quantity }}" min="1" required></td>
resources/views/orders/_order_items_edit.blade.php:37:            <td><input type="number" name="items[{{ $idx }}][price]" class="form-control" value="{{ $item->price }}" min="0" required></td>
resources/views/orders/_order_items_edit.blade.php:38:            <td class="item-total">{{ number_format($item->quantity * $item->price, 0, ',', '.') }}</td>
resources/views/orders/_order_items_edit.blade.php:39:            <td><button type="button" class="btn btn-danger btn-sm remove-item">X</button></td>
resources/views/orders/_order_items_edit.blade.php:61:        row += `<td><button type="button" class="btn btn-danger btn-sm remove-item">X</button></td>`;
resources/views/orders/create.blade.php:4:    <h4>Tạo đơn hàng mới</h4>
resources/views/orders/create.blade.php:8:            <label for="customer_id" class="form-label">Khách hàng</label>
resources/views/orders/create.blade.php:12:                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
resources/views/orders/create.blade.php:17:            <label for="user_id" class="form-label">Nhân viên phụ trách</label>
resources/views/orders/create.blade.php:21:                    <option value="{{ $user->id }}">{{ $user->name }}</option>
resources/views/orders/create.blade.php:26:            <label for="status" class="form-label">Trạng thái</label>
resources/views/orders/create.blade.php:33:        <button type="submit" class="btn btn-primary">Tạo đơn hàng</button>
resources/views/orders/create.blade.php:34:        <a href="{{ route('orders.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/orders/_customer_list.blade.php:4:            <th>Chọn</th>
resources/views/orders/_customer_list.blade.php:5:            <th>Tên</th>
resources/views/orders/_customer_list.blade.php:6:            <th>Email</th>
resources/views/orders/_customer_list.blade.php:7:            <th>Số điện thoại</th>
resources/views/orders/_customer_list.blade.php:16:                <td>{{ $customer->name }}</td>
resources/views/orders/_customer_list.blade.php:17:                <td>{{ $customer->email }}</td>
resources/views/orders/_customer_list.blade.php:18:                <td>{{ $customer->phone }}</td>
resources/views/orders/show.blade.php:5:        <h4 class="mb-0">Chi tiết đơn hàng #{{ $order->code }}</h4>
resources/views/orders/show.blade.php:6:        <a href="{{ route('orders.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
resources/views/orders/show.blade.php:13:                    <p><strong>Khách hàng:</strong> {{ $order->customer->name ?? '' }}</p>
resources/views/orders/show.blade.php:14:                    <p><strong>Nhân viên:</strong> {{ $order->user->name ?? '' }}</p>
resources/views/orders/show.blade.php:15:                    <p><strong>Tổng tiền:</strong> {{ number_format($order->total, 0, ',', '.') }} đ</p>
resources/views/orders/show.blade.php:16:                    <p><strong>Ngày tạo:</strong> {{ $order->created_at }}</p>
resources/views/orders/show.blade.php:19:                    <p><strong>Trạng thái hiện tại:</strong> {{ $statusLabels[$order->status] ?? $order->status }}</p>
resources/views/orders/show.blade.php:20:                    <p><strong>Thanh toán:</strong> {{ $order->payment_status }}</p>
resources/views/orders/show.blade.php:21:                    <p><strong>Giao hàng:</strong> {{ $order->delivery_status }}</p>
resources/views/orders/show.blade.php:23:                        <p><strong>Đang chờ duyệt:</strong> Bước {{ $currentPendingApproval->step->step_order }} (Role: {{ $currentPendingApproval->step->role_slug }})</p>
resources/views/orders/show.blade.php:33:                    <button type="submit" class="btn btn-warning">Xác nhận đóng hàng</button>
resources/views/orders/show.blade.php:44:                        <input type="text" name="note" class="form-control" placeholder="Ghi chú đóng hàng (optional)">
resources/views/orders/show.blade.php:47:                        <button type="submit" class="btn btn-success w-100">Hoàn thiện đóng hàng</button>
resources/views/orders/show.blade.php:55:                    <button type="submit" class="btn btn-primary">Lấy hàng</button>
resources/views/orders/show.blade.php:66:                        <input type="text" name="note" class="form-control" placeholder="Ghi chú giao hàng (optional)">
resources/views/orders/show.blade.php:79:                            <h6 class="mb-2">Thanh toán</h6>
resources/views/orders/show.blade.php:81:                                <input type="number" step="0.01" min="0" name="amount" class="form-control" placeholder="Số tiền thanh toán" required>
resources/views/orders/show.blade.php:92:                                <input type="text" name="note" class="form-control" placeholder="Ghi chú (optional)">
resources/views/orders/show.blade.php:94:                            <button type="submit" class="btn btn-success">Hoàn thiện đơn hàng</button>
resources/views/orders/show.blade.php:101:                                <h6 class="mb-2">Refund</h6>
resources/views/orders/show.blade.php:102:                                <p class="text-muted mb-2">Tạo đơn hoàn trả liên kết với đơn gốc nếu khách không nhận hàng hoặc trả hàng.</p>
resources/views/orders/show.blade.php:104:                            <button type="submit" class="btn btn-danger" onclick="return confirm('Xác nhận tạo yêu cầu hoàn trả cho đơn này?')">Refund</button>
resources/views/orders/show.blade.php:113:                    <button type="submit" class="btn btn-danger" onclick="return confirm('Hủy đơn sẽ release hàng đã booking. Bạn chắc chắn?')">Hủy đơn</button>
resources/views/orders/show.blade.php:121:            <h5 class="card-title mb-0">Xét duyệt đơn hàng</h5>
resources/views/orders/show.blade.php:132:                        <textarea name="note" class="form-control" rows="2" placeholder="Ghi chú duyệt (không bắt buộc)"></textarea>
resources/views/orders/show.blade.php:134:                    <button type="submit" class="btn btn-success">Approve</button>
resources/views/orders/show.blade.php:139:                        <textarea name="note" class="form-control" rows="2" placeholder="Lý do từ chối"></textarea>
resources/views/orders/show.blade.php:141:                    <button type="submit" class="btn btn-danger">Reject</button>
resources/views/orders/show.blade.php:144:                <p class="mb-0 text-muted">Bạn không có quyền duyệt bước hiện tại hoặc đơn đã không còn ở trạng thái chờ duyệt.</p>
resources/views/orders/show.blade.php:151:            <h5 class="card-title mb-0">Lịch sử xử lý đơn hàng</h5>
resources/views/orders/show.blade.php:158:                            <th>Thời gian</th>
resources/views/orders/show.blade.php:159:                            <th>Người dùng</th>
resources/views/orders/show.blade.php:160:                            <th>Vai trò</th>
resources/views/orders/show.blade.php:161:                            <th>Hành động</th>
resources/views/orders/show.blade.php:162:                            <th>Trạng thái trước</th>
resources/views/orders/show.blade.php:163:                            <th>Trạng thái sau</th>
resources/views/orders/show.blade.php:164:                            <th>Ghi chú</th>
resources/views/orders/show.blade.php:170:                                <td>{{ $history->created_at }}</td>
resources/views/orders/show.blade.php:171:                                <td>{{ $history->user->name ?? '-' }}</td>
resources/views/orders/show.blade.php:172:                                <td>{{ $history->role ?? '-' }}</td>
resources/views/orders/show.blade.php:173:                                <td>{{ $history->action }}</td>
resources/views/orders/show.blade.php:174:                                <td>{{ $history->status_before ?? '-' }}</td>
resources/views/orders/show.blade.php:175:                                <td>{{ $history->status_after ?? '-' }}</td>
resources/views/orders/show.blade.php:176:                                <td>{{ $history->note ?? '-' }}</td>
resources/views/orders/show.blade.php:180:                                <td colspan="7" class="text-center">Chưa có dữ liệu lịch sử xử lý.</td>
resources/views/orders/show.blade.php:191:            <h5 class="card-title mb-0">Lịch sử xét duyệt</h5>
resources/views/orders/show.blade.php:198:                            <th>Bước</th>
resources/views/orders/show.blade.php:199:                            <th>Role</th>
resources/views/orders/show.blade.php:200:                            <th>Trạng thái</th>
resources/views/orders/show.blade.php:201:                            <th>Người xử lý</th>
resources/views/orders/show.blade.php:202:                            <th>Thời gian</th>
resources/views/orders/show.blade.php:203:                            <th>Ghi chú</th>
resources/views/orders/show.blade.php:209:                                <td>{{ $approval->step->step_order ?? '' }}</td>
resources/views/orders/show.blade.php:210:                                <td>{{ $approval->step->role_slug ?? '' }}</td>
resources/views/orders/show.blade.php:211:                                <td>{{ $approval->status }}</td>
resources/views/orders/show.blade.php:212:                                <td>{{ $approval->approver->name ?? '' }}</td>
resources/views/orders/show.blade.php:213:                                <td>{{ $approval->approved_at }}</td>
resources/views/orders/show.blade.php:214:                                <td>{{ $approval->note }}</td>
resources/views/orders/show.blade.php:218:                                <td colspan="6" class="text-center">Chưa có dữ liệu xét duyệt.</td>
resources/views/orders/show.blade.php:227:    <h5>Danh sách sản phẩm</h5>
resources/views/orders/show.blade.php:231:                <th>Sản phẩm</th>
resources/views/orders/show.blade.php:232:                <th>Biến thể</th>
resources/views/orders/show.blade.php:233:                <th>Số lượng</th>
resources/views/orders/show.blade.php:235:                <th>Thành tiền</th>
resources/views/orders/show.blade.php:241:                <td>{{ $item->variant->product->name ?? '' }}</td>
resources/views/orders/show.blade.php:242:                <td>{{ $item->variant->variant_name ?? ($item->variant->sku ?? '') }}</td>
resources/views/orders/show.blade.php:243:                <td>{{ $item->quantity }}</td>
resources/views/orders/show.blade.php:244:                <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
resources/views/orders/show.blade.php:245:                <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
resources/views/orders/show.blade.php:251:        <a href="{{ route('transactions.create', ['order_id' => $order->id]) }}" class="btn btn-success mb-3">+ Thêm giao dịch/Thanh toán</a>
resources/views/orders/show.blade.php:253:    <h5>Giao dịch liên quan</h5>
resources/views/orders/show.blade.php:257:                <th>ID</th>
resources/views/orders/show.blade.php:258:                <th>Số tiền</th>
resources/views/orders/show.blade.php:259:                <th>Loại</th>
resources/views/orders/show.blade.php:260:                <th>Phương thức</th>
resources/views/orders/show.blade.php:261:                <th>Ghi chú</th>
resources/views/orders/show.blade.php:262:                <th>Thời gian</th>
resources/views/orders/show.blade.php:268:                    <td>{{ $t->id }}</td>
resources/views/orders/show.blade.php:269:                    <td>{{ number_format($t->amount,0,',','.') }}</td>
resources/views/orders/show.blade.php:270:                    <td>{{ $t->type }}</td>
resources/views/orders/show.blade.php:271:                    <td>{{ $t->method }}</td>
resources/views/orders/show.blade.php:272:                    <td>{{ $t->note }}</td>
resources/views/orders/show.blade.php:273:                    <td>{{ $t->created_at }}</td>
resources/views/products/search-results.blade.php:16:                <p>Không tìm thấy sản phẩm nào phù hợp.</p>
resources/views/products/search-results.blade.php:34:                                        <h5><a href="{{ route('pages.variant_detail', $variant) }}" class=" text-uppercase ">{{ $variant->product->name }} - {{ $variant->name }}</a></h5>
resources/views/products/search-results.blade.php:36:                                            <p class="card-text">Thương hiệu: {{ $variant->product->brand->name }}</p>
resources/views/products/search-results.blade.php:39:                                            <p class="card-text">Mã sản phẩm: {{ $variant->sku }}</p>
resources/views/products/search-results.blade.php:41:                                        <p class="card-text">Giá: {{ number_format($variant->final_price, 0, '.', ',') }} VNĐ</p>
resources/views/products/search-results.blade.php:43:                                            <a href="{{ route('pages.variant_detail', $variant) }}" class="btn btn-info  btn-brand btn-sm">Chi tiết</a>
resources/views/products/list.blade.php:59:                <td>{{ $product->name }}</td> 
resources/views/products/list.blade.php:60:                <td>{{ $product->stock }}</td>
resources/views/products/list.blade.php:74:                            <a href="{{ route('products.edit', ['product' => $product->id, 'page' =>  request()->page, 'perPage' => $perPage ]) }}" class="btn btn-primary btn-sm">Sửa</a>
resources/views/products/list.blade.php:110:                            ]) }}">Trang trước</a>
resources/views/products/list.blade.php:129:                        ]) }}">Trang sau</a>
resources/views/products/edit.blade.php:7:            <h4 class="mb-0">Chỉnh sửa sản phẩm</h4> 
resources/views/products/edit.blade.php:27:                    <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
resources/views/products/edit.blade.php:41:                    <label for="category_id" class="form-label">Danh mục <span class="text-danger">*</span></label>
resources/views/products/edit.blade.php:58:                    <label for="brand_id" class="form-label">Brand</label>
resources/views/products/edit.blade.php:93:                    <label>Gallery</label>
resources/views/products/edit.blade.php:112:                    <label class="form-label">Biến thể</label>
resources/views/products/edit.blade.php:116:                                <th>SKU</th>
resources/views/products/edit.blade.php:117:                                <th>Size</th>
resources/views/products/edit.blade.php:118:                                <th>Chất lượng</th>
resources/views/products/edit.blade.php:119:                                <th>Ngày SX</th>
resources/views/products/edit.blade.php:120:                                <th>Hình ảnh</th>
resources/views/products/edit.blade.php:121:                                <th>Giá bán</th>
resources/views/products/edit.blade.php:122:                                <th>Ngày áp dụng</th>
resources/views/products/edit.blade.php:123:                                <th>Số lượng tồn</th>
resources/views/products/edit.blade.php:154:                                        <button type="button" class="btn btn-sm btn-secondary select-variant-image" data-variant-id="{{ $variant->id }}">Chọn ảnh</button>
resources/views/products/edit.blade.php:164:                                    <div>{{ $latestRule ? \Carbon\Carbon::parse($latestRule->start_date)->format('d/m/Y H:i') : '' }}</div>
resources/views/products/edit.blade.php:171:                                    <button type="button" class="btn btn-danger btn-sm remove-variant">X</button>
resources/views/products/edit.blade.php:173:                                    <a href="{{ route('variants.edit-price', $variant->id) }}" class="btn btn-sm btn-warning mt-1">Điều chỉnh giá</a>
resources/views/products/edit.blade.php:174:                                    <button type="button" class="btn btn-info btn-sm mt-1 clone-variant" title="Nhân bản biến thể" data-variant-id="{{ $variant->id }}">Nhân bản</button>
resources/views/products/edit.blade.php:175:                                    <button type="button" class="btn btn-success btn-sm mt-1 quick-edit-variant" title="Sửa nhanh" data-variant-id="{{ $variant->id }}">Sửa nhanh</button>
resources/views/products/edit.blade.php:188:                                                                    <h5 class="modal-title" id="variantImageModalLabel">Quản trị hình ảnh biến thể</h5>
resources/views/products/edit.blade.php:189:                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
resources/views/products/edit.blade.php:194:                                                                        <button type="button" class="btn btn-primary" id="btnSelectVariantImageFromLibrary">Chọn ảnh từ thư viện</button>
resources/views/products/edit.blade.php:202:                                                                    <button type="button" class="btn btn-success" id="btnApplyVariantImage">Gán ảnh cho biến thể</button>
resources/views/products/edit.blade.php:212:                    <label for="description" class="form-label">Mô tả</label>
resources/views/products/edit.blade.php:216:                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
resources/views/products/edit.blade.php:224:                    <button type="submit" class="btn btn-primary me-2">Cập nhật sản phẩm</button> 
resources/views/products/edit.blade.php:225:                    <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Hủy</a>  
resources/views/products/edit.blade.php:237:        <h5 class="modal-title">Chọn hình ảnh</h5>
resources/views/products/edit.blade.php:238:        <button type="button" class="close" data-dismiss="modal" aria-label="Close">×</button>
resources/views/products/edit.blade.php:253:        <h5 class="modal-title">Chọn ảnh</h5>
resources/views/products/edit.blade.php:261:        <button type="button" class="btn btn-primary" id="confirmMediaSelect" data-bs-dismiss="modal">Chọn</button>
resources/views/products/edit.blade.php:287:                            <button type="button" class="btn btn-sm btn-secondary select-variant-image" data-variant-id="new_${index}">Chọn ảnh</button>
resources/views/products/edit.blade.php:293:                    <td><button type="button" class="btn btn-danger btn-sm remove-variant">X</button></td>
resources/views/products/edit.blade.php:429:        document.getElementById('variant-image-preview-modal').innerHTML = currentVariantImageUrl ? `<img src="${currentVariantImageUrl}" width="120" class="img-thumbnail">` : '<span class="text-muted">Chưa có hình ảnh</span>';
resources/views/products/form.blade.php:18:        <h5 class="modal-title">Chọn ảnh từ Media Library</h5>
resources/views/products/form.blade.php:38:            <label for="category_id">Category</label>
resources/views/products/form.blade.php:41:                    <option value="{{ $category->id }}" {{ isset($product) && $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
resources/views/products/form.blade.php:47:            <label for="brand_id">Brand</label>
resources/views/products/form.blade.php:49:                <option value="">Select a brand</option>
resources/views/products/form.blade.php:51:                    <option value="{{ $brand->id }}" {{ isset($product) && $product->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
resources/views/products/_quick-edit-form.blade.php:6:        <label>Tên sản phẩm</label>
resources/views/products/_quick-edit-form.blade.php:10:        <label>Giá</label>
resources/views/products/_quick-edit-form.blade.php:14:        <label>Tồn kho</label>
resources/views/products/_quick-edit-form.blade.php:20:            <button type="button" class="btn btn-primary btn-sm choose-image-btn" data-product-id="{{ $product->id }}">Chọn ảnh</button>
resources/views/products/_quick-edit-form.blade.php:24:    <button type='submit' class='btn btn-primary btn-sm'>Lưu</button>
resources/views/products/_quick-edit-form.blade.php:25:    <button type='button' class='btn btn-secondary btn-sm cancel-quick-edit'>Hủy</button>
resources/views/products/index.blade.php:25:                <option value="all" {{ ($statusFilter ?? 'active') === 'all' ? 'selected' : '' }}>Tất cả sản phẩm</option>
resources/views/products/index.blade.php:26:                <option value="active" {{ ($statusFilter ?? 'active') === 'active' ? 'selected' : '' }}>Sản phẩm đang hoạt động</option>
resources/views/products/index.blade.php:27:                <option value="deleted" {{ ($statusFilter ?? 'active') === 'deleted' ? 'selected' : '' }}>Sản phẩm đã xóa</option>
resources/views/products/index.blade.php:76:                        <th>Brand</th>
resources/views/products/index.blade.php:77:                        <th>Category</th>
resources/views/products/index.blade.php:78:                        <th>Trạng thái</th>
resources/views/products/index.blade.php:109:                        <span id="product-image-{{ $product->id }}">No image</span>
resources/views/products/index.blade.php:118:                    <td>{{ $product->brand->name ?? '' }}</td>
resources/views/products/index.blade.php:119:                    <td>{{ $product->category->name ?? '' }}</td>
resources/views/products/index.blade.php:127:                <td id="product-stock-{{ $product->id }}">{{ $product->stock ?? '' }}</td>
resources/views/products/index.blade.php:146:                            <a href="{{ route('products.edit', ['product' => $product->id, 'page' =>  request()->page, 'perPage' => $perPage ]) }}" class="btn btn-primary btn-sm">Sửa</a>
resources/views/products/index.blade.php:192:                            ]) }}">Trang trước</a>
resources/views/products/index.blade.php:211:                        ]) }}">Trang sau</a>
resources/views/products/index.blade.php:276:                        $('#product-image-' + id).replaceWith('<span id="product-image-' + id + '">No image</span>');
resources/views/products/create.blade.php:13:                    <h4 class="mb-0">Thêm sản phẩm mới</h4>
resources/views/products/create.blade.php:34:                            <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
resources/views/products/create.blade.php:47:                                <label for="category_id">Danh mục:</label>
resources/views/products/create.blade.php:80:                            <label>Gallery</label>
resources/views/products/create.blade.php:81:                            <button type="button" onclick="openMediaPopup()">Chọn ảnh</button>
resources/views/products/create.blade.php:89:                            <label for="description" class="form-label">Mô tả</label>
resources/views/products/create.blade.php:96:                                    <input type="text" id="ai-desc-requirement" class="form-control" placeholder="Yêu cầu mô tả (ví dụ: nhấn mạnh chất lượng, phù hợp trẻ em...)">
resources/views/products/create.blade.php:99:                                    <input type="number" id="ai-word-count" value="80" min="20" max="500" class="form-control" title="Số từ" placeholder="Số từ">
resources/views/products/create.blade.php:102:                                    <button type="button" class="btn btn-outline-info w-100" id="btn-ai-description">Tạo mô tả AI</button>
resources/views/products/create.blade.php:109:                                <strong>Gợi ý:</strong>
resources/views/products/create.blade.php:111:                                    <li>Nhấn mạnh chất lượng sản phẩm, độ bền cao</li>
resources/views/products/create.blade.php:112:                                    <li>Phù hợp cho trẻ em, an toàn khi sử dụng</li>
resources/views/products/create.blade.php:113:                                    <li>Thích hợp làm quà tặng, đóng gói đẹp</li>
resources/views/products/create.blade.php:114:                                    <li>Nêu bật tính năng nổi bật, công nghệ mới</li>
resources/views/products/create.blade.php:115:                                    <li>Mô tả cảm giác khi sử dụng sản phẩm</li>
resources/views/products/create.blade.php:116:                                    <li>Hướng đến khách hàng nữ, phong cách thời trang</li>
resources/views/products/create.blade.php:118:                                    <li>So sánh với sản phẩm cùng loại trên thị trường</li>
resources/views/products/create.blade.php:128:                            <label for="price" class="form-label">Giá <span class="text-danger">*</span></label>
resources/views/products/create.blade.php:143:                            <label for="price" class="form-label">Stock</label>
resources/views/products/create.blade.php:157:                            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Hủy</a>
resources/views/products/create.blade.php:158:                            <button type="submit" class="btn btn-success">Lưu sản phẩm</button>
resources/views/products/show.blade.php:8:                <h4 class="mb-0">Chi tiết sản phẩm</h4>
resources/views/products/show.blade.php:9:                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">Chỉnh sửa</a>
resources/views/products/show.blade.php:18:                        <label for="name" class="form-label fw-bold">Tên sản phẩm</label>
resources/views/products/show.blade.php:19:                        <p>{{ $product->name }}</p>
resources/views/products/show.blade.php:24:                        <label for="category_id" class="form-label fw-bold">Danh mục</label>
resources/views/products/show.blade.php:25:                        <p>{{ $product->category->name ?? 'N/A' }}</p>
resources/views/products/show.blade.php:30:                        <label for="brand_id" class="form-label fw-bold">Thương hiệu</label>
resources/views/products/show.blade.php:31:                        <p>{{ $product->brand->name ?? 'N/A' }}</p>
resources/views/products/show.blade.php:36:                        <label for="description" class="form-label fw-bold">Mô tả</label>
resources/views/products/show.blade.php:37:                        <div>{!! nl2br(e($product->description)) !!}</div>
resources/views/products/show.blade.php:49:                                <p class="text-muted">Không có ảnh</p>
resources/views/products/show.blade.php:56:                        <label class="form-label fw-bold">Gallery</label>
resources/views/products/show.blade.php:65:                                <p class="text-muted">Không có ảnh trong gallery</p>
resources/views/products/show.blade.php:75:                <label class="form-label fw-bold">Biến thể</label>
resources/views/products/show.blade.php:80:                                <th>SKU</th>
resources/views/products/show.blade.php:81:                                <th>Size</th>
resources/views/products/show.blade.php:82:                                <th>Chất lượng</th>
resources/views/products/show.blade.php:83:                                <th>Ngày SX</th>
resources/views/products/show.blade.php:84:                                <th>Hình ảnh</th>
resources/views/products/show.blade.php:85:                                <th>Giá bán</th>
resources/views/products/show.blade.php:86:                                <th>Số lượng tồn</th>
resources/views/products/show.blade.php:92:                                <td>{{ $variant->sku }}</td>
resources/views/products/show.blade.php:93:                                <td>{{ $variant->size ?? '-' }}</td>
resources/views/products/show.blade.php:94:                                <td>{{ $variant->quality ?? '-' }}</td>
resources/views/products/show.blade.php:95:                                <td>{{ $variant->production_date ? \Carbon\Carbon::parse($variant->production_date)->format('d/m/Y') : '-' }}</td>
resources/views/products/show.blade.php:100:                                        <span class="text-muted">N/A</span>
resources/views/products/show.blade.php:103:                                <td>{{ number_format($variant->final_price, 0, ',', '.') }} đ</td>
resources/views/products/show.blade.php:104:                                <td>{{ $variant->stock }}</td>
resources/views/products/show.blade.php:108:                                <td colspan="7" class="text-center">Sản phẩm này chưa có biến thể nào.</td>
resources/views/products/show.blade.php:118:                <a href="{{ url()->previous(route('products.index')) }}" class="btn btn-secondary">Quay lại</a>
resources/views/errors/403.blade.php:6:        <p>Xin lỗi, bạn không có quyền truy cập vào trang này.</p>
resources/views/errors/403.blade.php:7:        <a href="javascript:history.go(-1);">Quay lại trang chủ</a>
resources/views/admin/notifications/index.blade.php:6:        <h4 class="mb-0">Thong bao admin</h4>
resources/views/admin/notifications/index.blade.php:9:            <button type="submit" class="btn btn-sm btn-outline-primary">Danh dau tat ca da doc</button>
resources/views/admin/notifications/index.blade.php:19:                            <th>Tieu de</th>
resources/views/admin/notifications/index.blade.php:20:                            <th>Noi dung</th>
resources/views/admin/notifications/index.blade.php:21:                            <th>Thoi gian</th>
resources/views/admin/notifications/index.blade.php:22:                            <th>Trang thai</th>
resources/views/admin/notifications/index.blade.php:29:                                <td>{{ $notification->data['title'] ?? 'Thong bao' }}</td>
resources/views/admin/notifications/index.blade.php:30:                                <td>{{ $notification->data['message'] ?? '-' }}</td>
resources/views/admin/notifications/index.blade.php:31:                                <td>{{ optional($notification->created_at)->format('d/m/Y H:i') }}</td>
resources/views/admin/notifications/index.blade.php:34:                                        <span class="badge bg-warning text-dark">Chua doc</span>
resources/views/admin/notifications/index.blade.php:36:                                        <span class="badge bg-success">Da doc</span>
resources/views/admin/notifications/index.blade.php:42:                                        <button type="submit" class="btn btn-sm btn-primary">Xem su kien</button>
resources/views/admin/notifications/index.blade.php:48:                                <td colspan="5" class="text-center text-muted">Chua co thong bao nao.</td>
resources/views/admin/events/index.blade.php:6:        <h4 class="mb-0">Nhat ky su kien admin</h4>
resources/views/admin/events/index.blade.php:7:        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">Xem thong bao</a>
resources/views/admin/events/index.blade.php:14:                    <label class="form-label">Loai su kien</label>
resources/views/admin/events/index.blade.php:16:                        <option value="">Tat ca</option>
resources/views/admin/events/index.blade.php:23:                    <label class="form-label">Hanh dong</label>
resources/views/admin/events/index.blade.php:25:                        <option value="">Tat ca</option>
resources/views/admin/events/index.blade.php:32:                    <label class="form-label">Tu khoa</label>
resources/views/admin/events/index.blade.php:33:                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Tim theo tieu de/noi dung">
resources/views/admin/events/index.blade.php:36:                    <button class="btn btn-primary" type="submit">Loc</button>
resources/views/admin/events/index.blade.php:47:                        <th>Thoi gian</th>
resources/views/admin/events/index.blade.php:48:                        <th>Tieu de</th>
resources/views/admin/events/index.blade.php:49:                        <th>Loai</th>
resources/views/admin/events/index.blade.php:50:                        <th>Hanh dong</th>
resources/views/admin/events/index.blade.php:51:                        <th>Nguoi thuc hien</th>
resources/views/admin/events/index.blade.php:52:                        <th>Noi dung</th>
resources/views/admin/events/index.blade.php:59:                            <td>{{ optional($event->created_at)->format('d/m/Y H:i:s') }}</td>
resources/views/admin/events/index.blade.php:60:                            <td>{{ $event->title }}</td>
resources/views/admin/events/index.blade.php:61:                            <td><span class="badge bg-light text-dark">{{ $event->event_type }}</span></td>
resources/views/admin/events/index.blade.php:62:                            <td><span class="badge bg-info text-dark">{{ $event->action }}</span></td>
resources/views/admin/events/index.blade.php:63:                            <td>{{ $event->actor->name ?? 'System' }}</td>
resources/views/admin/events/index.blade.php:64:                            <td>{{ $event->message ?? '-' }}</td>
resources/views/admin/events/index.blade.php:67:                                    <a href="{{ $event->url }}" class="btn btn-sm btn-outline-primary">Mo doi tuong</a>
resources/views/admin/events/index.blade.php:73:                            <td colspan="7" class="text-center text-muted">Chua co su kien nao duoc ghi nhan.</td>
resources/views/admin/post-categories/edit.blade.php:5:        <h1>Edit Post Category</h1>
resources/views/admin/post-categories/edit.blade.php:10:                <label for="name">Name</label>
resources/views/admin/post-categories/edit.blade.php:13:            <button type="submit" class="btn btn-primary">Update</button>
resources/views/admin/post-categories/index.blade.php:5:        <h1>Post Categories</h1>
resources/views/admin/post-categories/index.blade.php:6:        <a href="{{ route('admin.post-categories.create') }}" class="btn btn-primary">Create Category</a>
resources/views/admin/post-categories/index.blade.php:10:                    <th>ID</th>
resources/views/admin/post-categories/index.blade.php:11:                    <th>Name</th>
resources/views/admin/post-categories/index.blade.php:12:                    <th>Actions</th>
resources/views/admin/post-categories/index.blade.php:18:                        <td>{{ $category->id }}</td>
resources/views/admin/post-categories/index.blade.php:19:                        <td>{{ $category->name }}</td>
resources/views/admin/post-categories/index.blade.php:21:                            <a href="{{ route('admin.post-categories.edit', $category->id) }}" class="btn btn-secondary">Edit</a>
resources/views/admin/post-categories/index.blade.php:25:                                <button type="submit" class="btn btn-danger">Delete</button>
resources/views/admin/post-categories/create.blade.php:5:        <h1>Create Post Category</h1>
resources/views/admin/post-categories/create.blade.php:9:                <label for="name">Name</label>
resources/views/admin/post-categories/create.blade.php:12:            <button type="submit" class="btn btn-primary">Create</button>
resources/views/admin/pages/edit.blade.php:7:        <h1>Edit Page</h1>
resources/views/admin/pages/edit.blade.php:12:                <label for="title">Title</label>
resources/views/admin/pages/edit.blade.php:16:                <label for="slug">Slug</label>
resources/views/admin/pages/edit.blade.php:20:                <label for="content">Content</label>
resources/views/admin/pages/edit.blade.php:35:            <button type="submit" class="btn btn-primary">Update</button>
resources/views/admin/pages/index.blade.php:5:        <h1>Pages</h1>
resources/views/admin/pages/index.blade.php:6:        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">Create Page</a>
resources/views/admin/pages/index.blade.php:10:                    <th>ID</th>
resources/views/admin/pages/index.blade.php:11:                    <th>Title</th>
resources/views/admin/pages/index.blade.php:12:                    <th>Slug</th>
resources/views/admin/pages/index.blade.php:13:                    <th>Actions</th>
resources/views/admin/pages/index.blade.php:19:                        <td>{{ $page->id }}</td>
resources/views/admin/pages/index.blade.php:20:                        <td>{{ $page->title }}</td>
resources/views/admin/pages/index.blade.php:21:                        <td>{{ $page->slug }}</td>
resources/views/admin/pages/index.blade.php:23:                            <a href="{{ route('admin.pages.edit', $page->id) }}" class="btn btn-secondary">Edit</a>
resources/views/admin/pages/index.blade.php:27:                                <button type="submit" class="btn btn-danger">Delete</button>
resources/views/admin/pages/create.blade.php:5:        <h1>Create Pagess</h1>
resources/views/admin/pages/create.blade.php:9:                <label for="title">Title</label>
resources/views/admin/pages/create.blade.php:13:                <label for="slug">Slug</label>
resources/views/admin/pages/create.blade.php:17:                <label for="content">Content</label> 
resources/views/admin/pages/create.blade.php:18:                <textarea name="content" id="editor">{{ old('content', $post->content ?? '') }}</textarea>
resources/views/admin/pages/create.blade.php:31:            <button type="submit" class="btn btn-primary">Create</button>
resources/views/admin/posts/edit.blade.php:5:        <h1>Edit Post</h1>
resources/views/admin/posts/edit.blade.php:10:                <label for="title">Title</label>
resources/views/admin/posts/edit.blade.php:14:                <label for="content">Content</label>
resources/views/admin/posts/edit.blade.php:15:                <textarea name="content" id="content" class="form-control">{{ $post->content }}</textarea>
resources/views/admin/posts/edit.blade.php:18:                <label for="image">Image</label>
resources/views/admin/posts/edit.blade.php:24:            <button type="submit" class="btn btn-primary">Update</button>
resources/views/admin/posts/index.blade.php:5:        <h1>Posts</h1>
resources/views/admin/posts/index.blade.php:6:        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">Create Post</a>
resources/views/admin/posts/index.blade.php:10:                    <th>ID</th>
resources/views/admin/posts/index.blade.php:11:                    <th>Title</th>
resources/views/admin/posts/index.blade.php:12:                    <th>Actions</th>
resources/views/admin/posts/index.blade.php:18:                        <td>{{ $post->id }}</td>
resources/views/admin/posts/index.blade.php:19:                        <td>{{ $post->title }}</td>
resources/views/admin/posts/index.blade.php:21:                            <a href="{{ route('admin.posts.edit', $post->id) }}" class="btn btn-secondary">Edit</a>
resources/views/admin/posts/index.blade.php:25:                                <button type="submit" class="btn btn-danger">Delete</button>
resources/views/admin/posts/create.blade.php:5:        <h1>Create Post</h1>
resources/views/admin/posts/create.blade.php:9:                <label for="title">Title</label>
resources/views/admin/posts/create.blade.php:13:                <label for="content">Content</label>
resources/views/admin/posts/create.blade.php:16:            <button type="submit" class="btn btn-primary">Create</button>
resources/views/admin/settings/index.blade.php:5:    <h2>Website Settings</h2>
resources/views/admin/settings/index.blade.php:16:            <label for="brand_name" class="form-label">Brand Name</label>
resources/views/admin/settings/index.blade.php:21:            <label for="slogan" class="form-label">Slogan</label>
resources/views/admin/settings/index.blade.php:26:            <label class="form-label">Logo</label>
resources/views/admin/settings/index.blade.php:38:            <button type="button" class="btn btn-info" id="btnSelectLogo">Chọn ảnh từ thư viện</button>
resources/views/admin/settings/index.blade.php:42:            <label class="form-label">Banner</label>
resources/views/admin/settings/index.blade.php:54:            <button type="button" class="btn btn-info" id="btnSelectBanner">Chọn ảnh từ thư viện</button>
resources/views/admin/settings/index.blade.php:58:            <label class="form-label">Footer Logo</label>
resources/views/admin/settings/index.blade.php:70:            <button type="button" class="btn btn-info" id="btnSelectFooterLogo">Chọn ảnh từ thư viện</button>
resources/views/admin/settings/index.blade.php:74:            <label for="address" class="form-label">Address</label>
resources/views/admin/settings/index.blade.php:75:            <textarea class="form-control" id="address" name="address" rows="3">{{ $settings['address']->value ?? '' }}</textarea>
resources/views/admin/settings/index.blade.php:79:            <label for="hotline" class="form-label">Hotline</label>
resources/views/admin/settings/index.blade.php:84:            <label for="email" class="form-label">Email</label>
resources/views/admin/settings/index.blade.php:89:            <label for="tax_number" class="form-label">Tax Number</label>
resources/views/admin/settings/index.blade.php:94:            <label class="form-label">Policy Page URL</label>
resources/views/admin/settings/index.blade.php:99:            <label class="form-label">Slider</label>
resources/views/admin/settings/index.blade.php:114:                    <button type="button" class="btn btn-info" id="btnSelectSlider{{ $i }}">Chọn ảnh từ thư viện</button>
resources/views/admin/settings/index.blade.php:119:        <button type="submit" class="btn btn-primary">Save Settings</button>
resources/views/admin/settings/index.blade.php:136:                        <h5 class='modal-title'>Chọn hình ảnh</h5>
resources/views/admin/brands/edit.blade.php:5:    <h1>Edit Brand</h1>
resources/views/admin/brands/edit.blade.php:10:            <label for="name">Name</label>
resources/views/admin/brands/edit.blade.php:14:            <label for="slug">Slug</label>
resources/views/admin/brands/edit.blade.php:18:            <label for="image">Image</label>
resources/views/admin/brands/edit.blade.php:25:            <label for="description">Description</label>
resources/views/admin/brands/edit.blade.php:26:            <textarea name="description" id="description" class="form-control">{{ $brand->description }}</textarea>
resources/views/admin/brands/edit.blade.php:28:        <button type="submit" class="btn btn-primary">Update</button>
resources/views/admin/brands/index.blade.php:5:    <h1>Brands</h1>
resources/views/admin/brands/index.blade.php:6:    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">Create Brand</a>
resources/views/admin/brands/index.blade.php:10:                <th>ID</th>
resources/views/admin/brands/index.blade.php:11:                <th>Name</th>
resources/views/admin/brands/index.blade.php:12:                <th>Slug</th>
resources/views/admin/brands/index.blade.php:13:                <th>Actions</th>
resources/views/admin/brands/index.blade.php:19:                <td>{{ $brand->id }}</td>
resources/views/admin/brands/index.blade.php:20:                <td>{{ $brand->name }}</td>
resources/views/admin/brands/index.blade.php:21:                <td>{{ $brand->slug }}</td>
resources/views/admin/brands/index.blade.php:23:                    <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-sm btn-primary">Edit</a>
resources/views/admin/brands/index.blade.php:27:                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
resources/views/admin/brands/create.blade.php:5:    <h1>Create Brand</h1>
resources/views/admin/brands/create.blade.php:9:            <label for="name">Name</label>
resources/views/admin/brands/create.blade.php:13:            <label for="slug">Slug</label>
resources/views/admin/brands/create.blade.php:17:            <label for="image">Image</label>
resources/views/admin/brands/create.blade.php:21:            <label for="description">Description</label>
resources/views/admin/brands/create.blade.php:24:        <button type="submit" class="btn btn-primary">Create</button>
resources/views/pages/contact.blade.php:36:    title="Liên hệ"
resources/views/pages/contact.blade.php:62:                        aria-label="One"></iframe>
resources/views/pages/contact.blade.php:68:                    <h3 class="mb-3 pb-2">Thông tin liên hệ</h3>  
resources/views/pages/contact.blade.php:70:                        <li><strong>Mã số thuế:</strong> {{ $settings['tax_number']->value ?? 'Chưa có' }}</li>
resources/views/pages/contact.blade.php:71:                        <li><strong>Địa chỉ:</strong> {{ $settings['address']->value ?? '' }}</li>
resources/views/pages/contact.blade.php:72:                        <li><strong>Điện thoại:</strong> {{ $settings['hotline']->value ?? '' }}</li>
resources/views/pages/contact.blade.php:73:                        <li><strong>Email: </strong> <a href="mailto: {{ $settings['email']->value ?? '' }}">{{ $settings['email']->value ?? '' }}</a></li>
resources/views/pages/contact.blade.php:76:                <h3> Gửi tin nhắn cho chúng tôi</h3>
resources/views/pages/contact.blade.php:77:                <p>Vui lòng điền vào biểu mẫu bên dưới để gửi tin nhắn cho chúng tôi.</p> 
resources/views/pages/contact.blade.php:82:                        <label for="name">Họ và tên</label>
resources/views/pages/contact.blade.php:86:                        <label for="email">Email</label>
resources/views/pages/contact.blade.php:90:                        <label for="message">Tin nhắn</label>
resources/views/pages/contact.blade.php:93:                    <button type="submit" class=" site-btn btn-brand">Gửi tin nhắn</button>
resources/views/pages/products_by_category.blade.php:6:    <title>Products</title>
resources/views/pages/products_by_category.blade.php:25:            <h2>Categories</h2>
resources/views/pages/products_by_category.blade.php:28:                    <li>{{ $category->name }}</li>
resources/views/pages/products_by_category.blade.php:33:            <h2>Products</h2>
resources/views/pages/products_by_category.blade.php:35:                <label for="month">Month:</label>
resources/views/pages/products_by_category.blade.php:37:                <label for="day">Day:</label>
resources/views/pages/products_by_category.blade.php:39:                <button type="submit">Filter</button>
resources/views/pages/products_by_category.blade.php:43:                <h3>{{ $product->name }}</h3>
resources/views/pages/products_by_category.blade.php:47:                            <li>{{ $variant->name }} - Price: {{ $variant->price }}</li>
resources/views/pages/products_by_category.blade.php:51:                    <p>No variants for this product.</p>
resources/views/pages/about.blade.php:12:        <h1>{{ $pages->first()->title ?? 'Giới thiệu' }}</h1>
resources/views/pages/about.blade.php:13:        <p>{!! $pages->first()->content ?? 'This is the about page.' !!}</p>
resources/views/pages/show.blade.php:7:                <h1>{{ $page->title }}</h1>
resources/views/pages/show.blade.php:8:                <p>{{ $page->content }}</p>
resources/views/site/product_list.blade.php:16:                    <h2>{{ $settings['brand_name']->value ?? 'My Website' }}</h2>
resources/views/site/product_list.blade.php:21:                <li><a href="#" class="nav-link px-2 link-secondary">Home</a></li>
resources/views/site/product_list.blade.php:25:                <p class="slogan">{{ $settings['slogan']->value ?? 'Your slogan here' }}</p>
resources/views/site/product_list.blade.php:27:                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
resources/views/site/product_list.blade.php:29:                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
resources/views/site/product_list.blade.php:41:            <h4>Categories</h4>
resources/views/site/product_list.blade.php:54:            <h1>Products</h1>
resources/views/site/product_list.blade.php:61:                                <th>Image</th>
resources/views/site/product_list.blade.php:62:                                <th>Product</th>
resources/views/site/product_list.blade.php:63:                                <th>Price</th>
resources/views/site/product_list.blade.php:77:                                <td>{{ $product->name }}</td>
resources/views/site/product_list.blade.php:80:                                    <a href="{{ route('pages.product_detail', $product->slug) }}" class="btn btn-info btn-sm">View Details</a>
resources/views/site/product_list.blade.php:100:                <h5>Thông tin công ty</h5>
resources/views/site/product_list.blade.php:101:                <p>{{ $settings['brand_name']->value ?? '' }}</p>
resources/views/site/product_list.blade.php:102:                <p>Mã số thuế: {{ $settings['tax_number']->value ?? 'Chưa có' }}</p>
resources/views/site/product_list.blade.php:105:                <h5>Liên hệ</h5>
resources/views/site/product_list.blade.php:106:                <p>Địa chỉ: {{ $settings['address']->value ?? '' }}</p>
resources/views/site/product_list.blade.php:107:                <p>Hotline: {{ $settings['hotline']->value ?? '' }}</p>
resources/views/site/product_list.blade.php:108:                <p>Email: {{ $settings['email']->value ?? '' }}</p>
resources/views/site/product_list.blade.php:111:                <h5>Chính sách</h5>
resources/views/site/product_list.blade.php:112:                <p><a href="{{ $settings['policy_page']->value ?? '#' }}">Chính sách và quy định</a></p>
resources/views/site/orders/show.blade.php:5:    <h1>Order Details</h1>
resources/views/site/orders/show.blade.php:14:                    <h5 class="mb-3">Thông tin đơn hàng</h5>
resources/views/site/orders/show.blade.php:15:                    <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
resources/views/site/orders/show.blade.php:16:                    <p><strong>Status:</strong> {{ $order->status }}</p>
resources/views/site/orders/show.blade.php:17:                    <p><strong>Payment Status:</strong> {{ $order->payment_status }}</p>
resources/views/site/orders/show.blade.php:18:                    <p><strong>Delivery Status:</strong> {{ $order->delivery_status }}</p>
resources/views/site/orders/show.blade.php:19:                    <p><strong>Total Amount:</strong> {{ number_format($order->total) }}</p>
resources/views/site/orders/show.blade.php:22:                    <h5 class="mb-3">Thông tin người nhận</h5>
resources/views/site/orders/show.blade.php:23:                    <p><strong>Tên người nhận:</strong> {{ $order->recipient_name }}</p>
resources/views/site/orders/show.blade.php:24:                    <p><strong>Số điện thoại:</strong> {{ $order->recipient_phone }}</p>
resources/views/site/orders/show.blade.php:25:                    <p><strong>Địa chỉ:</strong> {{ $order->recipient_address }}</p>
resources/views/site/orders/show.blade.php:27:                    <p><strong>Ghi chú:</strong> {{ $order->note }}</p>
resources/views/site/orders/show.blade.php:32:            <h5 class="mt-4">Order Items</h5>
resources/views/site/orders/show.blade.php:36:                        <th>Product</th>
resources/views/site/orders/show.blade.php:37:                        <th>Variant</th>
resources/views/site/orders/show.blade.php:38:                        <th>Quantity</th>
resources/views/site/orders/show.blade.php:39:                        <th>Price</th>
resources/views/site/orders/show.blade.php:40:                        <th>Subtotal</th>
resources/views/site/orders/show.blade.php:46:                        <td>{{ $item->variant->product->name }}</td>
resources/views/site/orders/show.blade.php:47:                        <td>{{ $item->variant->name }}</td>
resources/views/site/orders/show.blade.php:48:                        <td>{{ $item->quantity }}</td>
resources/views/site/orders/show.blade.php:49:                        <td>{{ number_format($item->price) }}</td>
resources/views/site/orders/show.blade.php:50:                        <td>{{ number_format($item->price * $item->quantity) }}</td>
resources/views/site/orders/show.blade.php:56:            <a href="{{ route('pages.my_orders') }}" class="btn btn-primary">Back to My Orders</a>
resources/views/site/my_dashboard.blade.php:5:    <h1>My Dashboard</h1>
resources/views/site/my_dashboard.blade.php:14:        <div class="card-header">User Information</div>
resources/views/site/my_dashboard.blade.php:16:            <p><strong>Name:</strong> {{ $user->name }}</p>
resources/views/site/my_dashboard.blade.php:17:            <p><strong>Email:</strong> {{ $user->email }}</p>
resources/views/site/my_dashboard.blade.php:27:        <div class="card-header">Customer Profile</div>
resources/views/site/my_dashboard.blade.php:32:                    <label for="name" class="form-label">Full Name</label>
resources/views/site/my_dashboard.blade.php:39:                    <label for="email" class="form-label">Email address</label>
resources/views/site/my_dashboard.blade.php:46:                    <label for="phone" class="form-label">Phone</label>
resources/views/site/my_dashboard.blade.php:53:                    <label for="dob" class="form-label">Date of Birth</label>
resources/views/site/my_dashboard.blade.php:61:                    <label for="gender" class="form-label">Gender</label>
resources/views/site/my_dashboard.blade.php:63:                        <option value="">Select Gender</option>
resources/views/site/my_dashboard.blade.php:64:                        <option value="male" {{ old('gender', $customer->gender ?? '') == 'male' ? 'selected' : '' }}>Male</option>
resources/views/site/my_dashboard.blade.php:65:                        <option value="female" {{ old('gender', $customer->gender ?? '') == 'female' ? 'selected' : '' }}>Female</option>
resources/views/site/my_dashboard.blade.php:66:                        <option value="other" {{ old('gender', $customer->gender ?? '') == 'other' ? 'selected' : '' }}>Other</option>
resources/views/site/my_dashboard.blade.php:73:                    <label for="avatar" class="form-label">Avatar</label>
resources/views/site/my_dashboard.blade.php:80:                    <label for="note" class="form-label">Note</label>
resources/views/site/my_dashboard.blade.php:81:                    <textarea class="form-control" id="note" name="note" rows="3">{{ old('note', $customer->note ?? '') }}</textarea>
resources/views/site/my_dashboard.blade.php:86:                <button type="submit" class="btn btn-primary">Update Profile</button>
resources/views/site/variants_list.blade.php:21:            <h1>SẢN PHẨMss</h1>
resources/views/site/variants_list.blade.php:28:                                <th>Image</th>
resources/views/site/variants_list.blade.php:29:                                <th>Product</th>
resources/views/site/variants_list.blade.php:30:                                <th>SKU</th>
resources/views/site/variants_list.blade.php:31:                                <th>Price</th>
resources/views/site/variants_list.blade.php:32:                                <th>Stock</th>
resources/views/site/variants_list.blade.php:48:                                <td>{{ $variant->product->name }}</td>
resources/views/site/variants_list.blade.php:49:                                <td>{{ $variant->sku }}</td>
resources/views/site/variants_list.blade.php:50:                                <td>{{ number_format($variant->latestPriceRule?->price ?? 0) }}</td>
resources/views/site/variants_list.blade.php:51:                                <td>{{ $variant->stock }}</td>
resources/views/site/variants_list.blade.php:54:                                    <a href="{{ route('pages.variant_detail', $variant->slug) }}" class="btn btn-info btn-sm">View</a>
resources/views/site/variants_list.blade.php:56:                                    <a href="{{ route('orders.create_new', ['variant_id' => $variant->id]) }}" class="btn btn-primary btn-sm">Lên đơn</a>
resources/views/site/partials/import_customer_modal.blade.php:6:                <h5 class="modal-title" id="importCustomerModalLabel">Import khách hàng từ file</h5>
resources/views/site/partials/import_customer_modal.blade.php:7:                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
resources/views/site/partials/import_customer_modal.blade.php:13:                        <label for="file" class="form-label">Chọn file (.xlsx, .csv)</label>
resources/views/site/partials/import_customer_modal.blade.php:20:                    <button type="submit" class="btn btn-primary">Import</button>
resources/views/site/partials/edit_customer_modal.blade.php:6:                <h5 class="modal-title" id="editCustomerModalLabel-{{ $customer->id }}">Sửa thông tin khách hàng</h5>
resources/views/site/partials/edit_customer_modal.blade.php:7:                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
resources/views/site/partials/edit_customer_modal.blade.php:14:                        <label for="name-{{ $customer->id }}" class="form-label">Tên</label>
resources/views/site/partials/edit_customer_modal.blade.php:18:                        <label for="email-{{ $customer->id }}" class="form-label">Email</label>
resources/views/site/partials/edit_customer_modal.blade.php:22:                        <label for="phone-{{ $customer->id }}" class="form-label">Điện thoại</label>
resources/views/site/partials/edit_customer_modal.blade.php:28:                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
resources/views/site/partials/add_customer_modal.blade.php:6:                <h5 class="modal-title" id="addCustomerModalLabel">Thêm khách hàng mới</h5>
resources/views/site/partials/add_customer_modal.blade.php:7:                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
resources/views/site/partials/add_customer_modal.blade.php:19:                        <label for="name" class="form-label">Tên</label>
resources/views/site/partials/add_customer_modal.blade.php:23:                        <label for="email" class="form-label">Email</label>
resources/views/site/partials/add_customer_modal.blade.php:33:                    <button type="submit" class="btn btn-primary">Lưu</button>
resources/views/site/categories/index.blade.php:4:    <h1>Categories</h1>
resources/views/site/variant_detail.blade.php:9:                        <h2>Sản phẩm</h2>
resources/views/site/variant_detail.blade.php:11:                            <a href="./"><i class="fa fa-home"></i> Trang chủ</a>
resources/views/site/variant_detail.blade.php:12:                            <a href="{{ route('pages.products_by_category') }}"><i class="fa fa-home"></i> Sản phẩm</a>
resources/views/site/variant_detail.blade.php:13:                            <span> {{ $product->name }}</span>
resources/views/site/variant_detail.blade.php:29:                    <h1>{{ $product->name }} - {{ $variant->name }}</h1>
resources/views/site/variant_detail.blade.php:53:                    <p><strong>SKU:</strong> {{ $variant->sku }}</p>
resources/views/site/variant_detail.blade.php:54:                    <p><strong>Giá:</strong> <span class="fs-4 text-danger">{{ number_format($variant->latestPriceRule?->price ?? 0) }} VNĐ</span></p>
resources/views/site/variant_detail.blade.php:55:                    <p><strong>Kho:</strong> {{ $variant->stock > 0 ? 'Còn hàng' : 'Hết hàng' }}</p>
resources/views/site/variant_detail.blade.php:64:                        <strong>Mô tả:</strong>
resources/views/site/variant_detail.blade.php:65:                        <div>{!! $product->description !!}</div>
resources/views/site/variant_detail.blade.php:70:                        <h5>Chia sẻ sản phẩm:</h5>
resources/views/site/variant_detail.blade.php:71:                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> Facebook</a>
resources/views/site/variant_detail.blade.php:72:                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('pages.variant_detail', $variant)) }}&text={{ urlencode($product->name) }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-twitter"></i> Twitter</a>
resources/views/site/variant_detail.blade.php:73:                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('pages.variant_detail', $variant)) }}&title={{ urlencode($product->name) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-linkedin"></i> LinkedIn</a>
resources/views/site/variant_detail.blade.php:74:                        <a href="https://zalo.me/share?url={{ urlencode(route('pages.variant_detail', $variant)) }}" target="_blank" class="btn btn-info btn-sm">Zalo</a>
resources/views/site/variant_detail.blade.php:86:                                <h4>Danh mục sản phẩm</h4>
resources/views/site/variant_detail.blade.php:92:                                            <a href="{{ route('pages.products_by_category', $category) }}">{{ $category->name }}</a>
resources/views/site/variant_detail.blade.php:93:                                            <span class="badge bg-primary rounded-pill">{{ $category->products_count }}</span>
resources/views/site/variant_detail.blade.php:101:                        <h3>Sản phẩm cùng loại</h3> 
resources/views/site/variant_detail.blade.php:117:                                                <h5 class="card-title"><a href="{{ route('pages.variant_detail', $other_variant) }}">{{ $other_variant->product->name }} - {{ $other_variant->name }}</a></h5>
resources/views/site/variant_detail.blade.php:119:                                                <p class="card-text text-danger">{{ number_format($other_variant->latestPriceRule->price, 0, ',', '.') }} VNĐ</p>
resources/views/site/variant_detail.blade.php:121:                                                <a href="{{ route('pages.variant_detail', $other_variant) }}" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
resources/views/site/variant_detail.blade.php:128:                            <p>Không có sản phẩm nào khác trong danh mục này.</p>
resources/views/site/product_detail.blade.php:22:            <h1>{{ $product->name }}</h1>
resources/views/site/product_detail.blade.php:24:                <p class="text-muted">Brand: {{ $product->brand->name }}</p>
resources/views/site/product_detail.blade.php:26:            <p class="text-muted">{{ $product->description }}</p>
resources/views/site/product_detail.blade.php:36:                                <option value="{{ $value->id }}">{{ $value->value }}</option>
resources/views/site/product_detail.blade.php:43:            <button id="reset-selection-btn" class="btn btn-sm btn-secondary mb-3">Reset Selection</button>
resources/views/site/product_detail.blade.php:53:                        <label for="quantity" class="form-label">Quantity</label>
resources/views/site/product_detail.blade.php:57:                        <button id="add-to-cart-btn" class="btn btn-primary w-100" disabled>Add to Cart</button>
resources/views/site/product_detail.blade.php:60:                 <p class="mt-2">Total: <strong id="total-price"></strong></p>
resources/views/site/posts/index.blade.php:4:    <h1>News</h1>
resources/views/site/posts/show.blade.php:4:    <h1>News Detail</h1>
resources/views/site/products_by_category.blade.php:111:                    <h2>{{ $settings['brand_name']->value ?? 'My Website' }}</h2>
resources/views/site/products_by_category.blade.php:116:                <li><a href="#" class="nav-link px-2 link-secondary">Home</a></li>
resources/views/site/products_by_category.blade.php:120:                <p class="slogan me-3 mb-0">{{ $settings['slogan']->value ?? 'Your slogan here' }}</p>
resources/views/site/products_by_category.blade.php:123:                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
resources/views/site/products_by_category.blade.php:125:                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
resources/views/site/products_by_category.blade.php:143:                <h5 class="text-uppercase fw-bold mb-2">Danh mục sản phẩm</h5>
resources/views/site/products_by_category.blade.php:182:                                <h5><a href="{{ route('pages.variant_detail', $variant->slug) }}" class="text-uppercase">{{ $variant->product->name }} - {{ $variant->name }}</a></h5>
resources/views/site/products_by_category.blade.php:184:                                    <p class="product-meta">Mã sản phẩm: {{ $variant->sku }}</p>
resources/views/site/products_by_category.blade.php:186:                                <p class="product-price">{{ number_format($variant->final_price ?? 0, 0, '.', ',') }} VNĐ</p>
resources/views/site/products_by_category.blade.php:188:                                    <a href="{{ route('pages.variant_detail', $variant->slug) }}" class="btn btn-info btn-sm">Chi tiết</a>
resources/views/site/cart.blade.php:14:            <h1>Shopping Cart</h1>
resources/views/site/cart.blade.php:20:                                <th>Product</th>
resources/views/site/cart.blade.php:21:                                <th>Price</th>
resources/views/site/cart.blade.php:22:                                <th>Quantity</th>
resources/views/site/cart.blade.php:23:                                <th>Subtotal</th>
resources/views/site/cart.blade.php:58:                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
resources/views/site/cart.blade.php:67:                    <a href="{{ route('cart.checkout') }}" class="btn btn-success">Tạo đơn hàng</a>
resources/views/site/my_orders.blade.php:5:    <h1>My Orders</h1>
resources/views/site/my_orders.blade.php:8:        <div class="card-header">Filter Orders</div>
resources/views/site/my_orders.blade.php:12:                    <label for="customer_id" class="form-label">Customer</label>
resources/views/site/my_orders.blade.php:14:                        <option value="">All customers</option>
resources/views/site/my_orders.blade.php:23:                    <label for="from_date" class="form-label">From Date</label>
resources/views/site/my_orders.blade.php:27:                    <label for="to_date" class="form-label">To Date</label>
resources/views/site/my_orders.blade.php:31:                    <button type="submit" class="btn btn-primary">Filter</button>
resources/views/site/my_orders.blade.php:38:        <div class="card-header">Order List</div>
resources/views/site/my_orders.blade.php:43:                        <th>Order Code</th>
resources/views/site/my_orders.blade.php:44:                        <th>Customer</th>
resources/views/site/my_orders.blade.php:45:                        <th>Total</th>
resources/views/site/my_orders.blade.php:46:                        <th>Status</th>
resources/views/site/my_orders.blade.php:47:                        <th>Date</th>
resources/views/site/my_orders.blade.php:54:                            <td>{{ $order->code }}</td>
resources/views/site/my_orders.blade.php:55:                            <td>{{ $order->customer->name ?? '' }}</td>
resources/views/site/my_orders.blade.php:56:                            <td>{{ number_format($order->total, 2) }}</td>
resources/views/site/my_orders.blade.php:57:                            <td>{{ $order->status }}</td>
resources/views/site/my_orders.blade.php:58:                            <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
resources/views/site/my_orders.blade.php:60:                                <a href="{{ route('site.orders.show', $order) }}" class="btn btn-info btn-sm">View</a>
resources/views/site/my_orders.blade.php:62:                                    <a href="{{ route('site.order-returns.create', $order) }}" class="btn btn-warning btn-sm">Tra hang</a>
resources/views/site/my_orders.blade.php:68:                            <td colspan="6" class="text-center">You have no orders.</td>
resources/views/site/variants.blade.php:5:<div class="vc_row wpb_row vc_row-fluid vc_custom_1490961090719 vc_row-has-fill sc_layouts_row sc_layouts_row_type_normal sc_layouts_hide_on_frontpage"><div class="wpb_column vc_column_container vc_col-sm-12 sc_layouts_column sc_layouts_column_align_center sc_layouts_column_icons_position_left"><div class="vc_column-inner"><div class="wpb_wrapper"><div id="sc_content_50654222" class="sc_content sc_content_default sc_float_center sc_content_width_1_1"><div class="sc_content_container"><div class="sc_layouts_item"><div id="sc_layouts_title_1126391177" class="sc_layouts_title"><div class="sc_layouts_title_title">			<h1 class="sc_layouts_title_caption">Find a Used Car</h1>
resources/views/site/variants.blade.php:6:			</div><div class="sc_layouts_title_breadcrumbs"><div class="breadcrumbs"><a class="breadcrumbs_item home" href="https://budgetcars.ancorathemes.com/">Home</a><span class="breadcrumbs_delimiter"></span><span class="breadcrumbs_item current">Find a Used Car</span></div></div></div><!-- /.sc_layouts_title --></div></div></div><!-- /.sc_content --></div></div></div></div>
resources/views/site/variants.blade.php:21:                    <h2>{{ $settings['brand_name']->value ?? 'My Website' }}</h2>
resources/views/site/variants.blade.php:26:                <li><a href="#" class="nav-link px-2 link-secondary">Home</a></li>
resources/views/site/variants.blade.php:30:                <p class="slogan">{{ $settings['slogan']->value ?? 'Your slogan here' }}</p>
resources/views/site/variants.blade.php:32:                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
resources/views/site/variants.blade.php:34:                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
resources/views/site/variants.blade.php:44:    <h1>Product Variants</h1>
resources/views/site/variants.blade.php:54:                        <button type="submit" class="btn btn-primary">Filter</button>
resources/views/site/variants.blade.php:69:                        <th>Product</th>
resources/views/site/variants.blade.php:70:                        <th>SKU</th>
resources/views/site/variants.blade.php:71:                        <th>Date</th>
resources/views/site/variants.blade.php:72:                        <th>Price</th>
resources/views/site/variants.blade.php:73:                        <th>Stock</th>
resources/views/site/variants.blade.php:81:                        <td><a href="{{ route('pages.variant_detail', $variant->slug) }}">{{ $variant->product->name }}</a></td>
resources/views/site/variants.blade.php:82:                        <td>{{ $variant->sku }}</td>
resources/views/site/variants.blade.php:83:                        <td>{{ $variant->production_date }}</td>
resources/views/site/variants.blade.php:84:                        <td>{{ number_format($variant->latestPriceRule?->price ?? 0) }}</td>
resources/views/site/variants.blade.php:85:                        <td>{{ $variant->stock }}</td>
resources/views/site/variants.blade.php:87:                            <a href="{{ route('pages.variant_detail', $variant->slug) }}" class="btn btn-info btn-sm">View</a>
resources/views/site/variants.blade.php:88:                            <button class="btn btn-success btn-sm order-btn" data-price="{{ $variant->latestPriceRule?->price ?? 0 }}">Order</button>
resources/views/site/variants.blade.php:90:                                <a href="{{ route('product-variants.edit', $variant->id) }}" class="btn btn-primary btn-sm">Edit</a>
resources/views/site/variants.blade.php:110:                <h5>Thông tin công ty</h5>
resources/views/site/variants.blade.php:111:                <p>{{ $settings['brand_name']->value ?? '' }}</p>
resources/views/site/variants.blade.php:112:                <p>Mã số thuế: {{ $settings['tax_number']->value ?? 'Chưa có' }}</p>
resources/views/site/variants.blade.php:115:                <h5>Liên hệ</h5>
resources/views/site/variants.blade.php:116:                <p>Địa chỉ: {{ $settings['address']->value ?? '' }}</p>
resources/views/site/variants.blade.php:117:                <p>Hotline: {{ $settings['hotline']->value ?? '' }}</p>
resources/views/site/variants.blade.php:118:                <p>Email: {{ $settings['email']->value ?? '' }}</p>
resources/views/site/variants.blade.php:121:                <h5>Chính sách</h5>
resources/views/site/variants.blade.php:122:                <p><a href="{{ $settings['policy_page']->value ?? '#' }}">Chính sách và quy định</a></p>
resources/views/site/variant_list.blade.php:16:                    <h2>{{ $settings['brand_name']->value ?? 'My Website' }}</h2>
resources/views/site/variant_list.blade.php:21:                <li><a href="#" class="nav-link px-2 link-secondary">Home</a></li>
resources/views/site/variant_list.blade.php:25:                <p class="slogan">{{ $settings['slogan']->value ?? 'Your slogan here' }}</p>
resources/views/site/variant_list.blade.php:27:                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Dashboard</a>
resources/views/site/variant_list.blade.php:29:                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
resources/views/site/variant_list.blade.php:41:            <h4>Categories</h4>
resources/views/site/variant_list.blade.php:54:            <h1>variants</h1>
resources/views/site/variant_list.blade.php:61:                                <th>Image</th>
resources/views/site/variant_list.blade.php:62:                                <th>variant</th>
resources/views/site/variant_list.blade.php:63:                                <th>Price</th>
resources/views/site/variant_list.blade.php:77:                                <td>{{ $variant->name }}</td>
resources/views/site/variant_list.blade.php:80:                                    <a href="{{ route('pages.variant_detail', $variant->slug) }}" class="btn btn-info btn-sm">View Details</a>
resources/views/site/variant_list.blade.php:100:                <h5>Thông tin công ty</h5>
resources/views/site/variant_list.blade.php:101:                <p>{{ $settings['brand_name']->value ?? '' }}</p>
resources/views/site/variant_list.blade.php:102:                <p>Mã số thuế: {{ $settings['tax_number']->value ?? 'Chưa có' }}</p>
resources/views/site/variant_list.blade.php:105:                <h5>Liên hệ</h5>
resources/views/site/variant_list.blade.php:106:                <p>Địa chỉ: {{ $settings['address']->value ?? '' }}</p>
resources/views/site/variant_list.blade.php:107:                <p>Hotline: {{ $settings['hotline']->value ?? '' }}</p>
resources/views/site/variant_list.blade.php:108:                <p>Email: {{ $settings['email']->value ?? '' }}</p>
resources/views/site/variant_list.blade.php:111:                <h5>Chính sách</h5>
resources/views/site/variant_list.blade.php:112:                <p><a href="{{ $settings['policy_page']->value ?? '#' }}">Chính sách và quy định</a></p>
resources/views/site/checkout.blade.php:7:            <h2>Thông tin đơn hàng</h2>
resources/views/site/checkout.blade.php:12:                        <h5 class="card-title">Thông tin người nhận</h5>
resources/views/site/checkout.blade.php:14:                            <label for="recipient_name" class="form-label">Họ tên người nhận</label>
resources/views/site/checkout.blade.php:23:                            <label for="recipient_phone" class="form-label">Số điện thoại</label>
resources/views/site/checkout.blade.php:41:                            <label for="note" class="form-label">Ghi chú</label>
resources/views/site/checkout.blade.php:54:                                        <th>Sản phẩm</th>
resources/views/site/checkout.blade.php:56:                                        <th>Số lượng</th>
resources/views/site/checkout.blade.php:57:                                        <th>Thành tiền</th>
resources/views/site/checkout.blade.php:85:                                        <td colspan="3" class="text-end"><strong>Tổng tiền:</strong></td>
resources/views/site/checkout.blade.php:95:                    <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary me-2">Quay lại giỏ hàng</a>
resources/views/site/checkout.blade.php:104:                    <h5 class="card-title">Tóm tắt đơn hàng</h5>
resources/views/site/checkout.blade.php:106:                        <span>Tạm tính:</span>
resources/views/site/checkout.blade.php:110:                        <strong>Tổng cộng:</strong>
resources/views/site/about.blade.php:4:    <h1>About Us</h1>
resources/views/site/my_customer.blade.php:5:    <h1>Khách hàng của bạn</h1>
resources/views/site/my_customer.blade.php:18:                    <span>Danh sách khách hàng</span>
resources/views/site/my_customer.blade.php:20:                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">Thêm mới</button>
resources/views/site/my_customer.blade.php:21:                        <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">Xóa đã chọn</button>
resources/views/site/my_customer.blade.php:22:                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#importCustomerModal">Import</button>
resources/views/site/my_customer.blade.php:33:                            <th>Tên</th>
resources/views/site/my_customer.blade.php:34:                            <th>Email</th>
resources/views/site/my_customer.blade.php:36:                            <th>Hành động</th>
resources/views/site/my_customer.blade.php:42:                                <td><input type="checkbox" name="ids[]" value="{{ $customer->id }}" class="customer-checkbox"></td>
resources/views/site/my_customer.blade.php:43:                                <td>{{ $customer->name }}</td>
resources/views/site/my_customer.blade.php:44:                                <td>{{ $customer->email }}</td>
resources/views/site/my_customer.blade.php:45:                                <td>{{ $customer->phone }}</td>
resources/views/site/my_customer.blade.php:47:                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCustomerModal-{{ $customer->id }}">Sửa</button>
resources/views/site/my_customer.blade.php:51:                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Xóa</button>
resources/views/site/home.blade.php:6:        <h1 class="display-5 fw-bold">Welcome to our website!</h1>
resources/views/site/home.blade.php:7:        <p class="col-md-8 fs-4">This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
resources/views/site/my_customer/order_create.blade.php:5:    <h1>Create Order for {{ $customer->name }}</h1>
resources/views/site/my_customer/order_create.blade.php:12:            <p><strong>Email:</strong> {{ $customer->email }}</p>
resources/views/site/my_customer/order_create.blade.php:13:            <p><strong>Phone:</strong> {{ $customer->phone }}</p>
resources/views/site/my_customer/order_create.blade.php:37:                            <th>Product</th>
resources/views/site/my_customer/order_create.blade.php:38:                            <th>Variant</th>
resources/views/site/my_customer/order_create.blade.php:39:                            <th>Price</th>
resources/views/site/my_customer/order_create.blade.php:40:                            <th>Quantity</th>
resources/views/site/my_customer/order_create.blade.php:47:                                    <td>{{ $product->name }}</td>
resources/views/site/my_customer/order_create.blade.php:48:                                    <td>{{ $variant->name }}</td>
resources/views/site/my_customer/order_create.blade.php:49:                                    <td>{{ number_format($variant->latestPriceRule->price ?? $variant->price) }}</td>
resources/views/site/my_customer/order_create.blade.php:61:                <button type="submit" class="btn btn-primary">Create Order</button>
resources/views/site/my_customer/order_create.blade.php:62:                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Cancel</a>
resources/views/site/my_customer/edit.blade.php:5:    <h1>Sửa thông tin khách hàng</h1>
resources/views/site/my_customer/edit.blade.php:8:        <div class="card-header">Thông tin khách hàng</div>
resources/views/site/my_customer/edit.blade.php:14:                    <label for="name" class="form-label">Tên</label>
resources/views/site/my_customer/edit.blade.php:18:                    <label for="email" class="form-label">Email</label>
resources/views/site/my_customer/edit.blade.php:25:                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
resources/views/site/my_customer/edit.blade.php:26:                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Hủy</a>
resources/views/site/my_customer/import.blade.php:5:    <h1>Import khách hàng</h1>
resources/views/site/my_customer/import.blade.php:8:        <div class="card-header">Import khách hàng từ file</div>
resources/views/site/my_customer/import.blade.php:13:                    <label for="file" class="form-label">Chọn file (.xlsx, .csv)</label>
resources/views/site/my_customer/import.blade.php:16:                <button type="submit" class="btn btn-primary">Import</button>
resources/views/site/my_customer/import.blade.php:17:                <a href="{{ asset('sample/customer_import_template.xlsx') }}" class="btn btn-link">Tải file mẫu</a>
resources/views/site/my_customer/import.blade.php:18:                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Quay lại</a>
resources/views/site/my_customer/import.blade.php:25:            <div class="card-header">Kết quả import</div>
resources/views/site/my_customer/import.blade.php:31:                    <h5>Các dòng bị lỗi:</h5>
resources/views/site/my_customer/import.blade.php:35:                                <th>Dòng</th>
resources/views/site/my_customer/import.blade.php:36:                                <th>Lỗi</th>
resources/views/site/my_customer/import.blade.php:37:                                <th>Dữ liệu</th>
resources/views/site/my_customer/index.blade.php:5:    <h1>Khách hàng của bạn</h1>
resources/views/site/my_customer/index.blade.php:26:                        <span>Danh sách khách hàng</span>
resources/views/site/my_customer/index.blade.php:28:                            <a href="{{ route('my_customer.create') }}" class="btn btn-primary btn-sm">Thêm mới</a>
resources/views/site/my_customer/index.blade.php:29:                            <a href="{{ route('my_customer.import_form') }}" class="btn btn-info btn-sm">Import</a>
resources/views/site/my_customer/index.blade.php:30:                            <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">Xóa đã chọn</button>
resources/views/site/my_customer/index.blade.php:39:                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm kiếm..." value="{{ $search ?? '' }}">
resources/views/site/my_customer/index.blade.php:43:                            <label class="input-group-text" for="per_page">Hiển thị</label>
resources/views/site/my_customer/index.blade.php:52:                    <button class="btn btn-primary btn-sm" type="submit">Lọc</button>
resources/views/site/my_customer/index.blade.php:64:                            <th>Tên</th>
resources/views/site/my_customer/index.blade.php:65:                            <th>Email</th>
resources/views/site/my_customer/index.blade.php:67:                                                            <th>Hành động</th>
resources/views/site/my_customer/index.blade.php:73:                                                            <td><input type="checkbox" name="ids[]" value="{{ $customer->id }}" class="customer-checkbox"></td>
resources/views/site/my_customer/index.blade.php:74:                                                            <td>{{ $customer->name }}</td>
resources/views/site/my_customer/index.blade.php:75:                                                            <td>{{ $customer->email }}</td>
resources/views/site/my_customer/index.blade.php:76:                                                            <td>{{ $customer->phone }}</td>
resources/views/site/my_customer/index.blade.php:79:                                                                    <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-info btn-sm">Xem đơn hàng</a>
resources/views/site/my_customer/index.blade.php:83:                                                                <button class="btn btn-info btn-sm quick-view-btn" data-id="{{ $customer->id }}">Xem nhanh đơn hàng</button>
resources/views/site/my_customer/index.blade.php:84:                                                                <a href="{{ route('my_customer.show', $customer) }}" class="btn btn-primary btn-sm">Xem</a>
resources/views/site/my_customer/index.blade.php:85:                                                                <a href="{{ route('my_customer.order.create', $customer) }}" class="btn btn-success btn-sm">Lên đơn</a>
resources/views/site/my_customer/index.blade.php:86:                                                                <a href="{{ route('my_customer.edit', $customer) }}" class="btn btn-warning btn-sm">Sửa</a>
resources/views/site/my_customer/index.blade.php:90:                                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Xóa</button>
resources/views/site/my_customer/_orders_quick_view.blade.php:2:    <div class="alert alert-info">No orders found for this customer.</div>
resources/views/site/my_customer/_orders_quick_view.blade.php:8:                <th>Order ID</th>
resources/views/site/my_customer/_orders_quick_view.blade.php:9:                <th>Date</th>
resources/views/site/my_customer/_orders_quick_view.blade.php:10:                <th>Total</th>
resources/views/site/my_customer/_orders_quick_view.blade.php:11:                <th>Status</th>
resources/views/site/my_customer/_orders_quick_view.blade.php:18:                        <button class="btn btn-secondary btn-sm toggle-products" data-bs-toggle="collapse" data-bs-target="#products-{{ $order->id }}">+</button>
resources/views/site/my_customer/_orders_quick_view.blade.php:20:                    <td>{{ $order->id }}</td>
resources/views/site/my_customer/_orders_quick_view.blade.php:21:                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
resources/views/site/my_customer/_orders_quick_view.blade.php:22:                    <td>{{ number_format($order->total_amount) }}</td>
resources/views/site/my_customer/_orders_quick_view.blade.php:23:                    <td>{{ $order->status }}</td>
resources/views/site/my_customer/_orders_quick_view.blade.php:30:                                    <th>Product</th>
resources/views/site/my_customer/_orders_quick_view.blade.php:31:                                    <th>Quantity</th>
resources/views/site/my_customer/_orders_quick_view.blade.php:32:                                    <th>Price</th>
resources/views/site/my_customer/_orders_quick_view.blade.php:38:                                        <td>{{ optional($item->product)->name }} ({{ optional($item->variant)->name }})</td>
resources/views/site/my_customer/_orders_quick_view.blade.php:39:                                        <td>{{ $item->quantity }}</td>
resources/views/site/my_customer/_orders_quick_view.blade.php:40:                                        <td>{{ number_format($item->price) }}</td>
resources/views/site/my_customer/create.blade.php:5:    <h1>Thêm khách hàng mới</h1>
resources/views/site/my_customer/create.blade.php:24:        <div class="card-header">Thông tin khách hàng</div>
resources/views/site/my_customer/create.blade.php:29:                    <label for="name" class="form-label">Tên</label>
resources/views/site/my_customer/create.blade.php:33:                    <label for="email" class="form-label">Email</label>
resources/views/site/my_customer/create.blade.php:40:                <button type="submit" class="btn btn-primary">Lưu</button>
resources/views/site/my_customer/create.blade.php:41:                <a href="{{ route('pages.my_customer') }}" class="btn btn-secondary">Hủy</a>
resources/views/site/my_customer/show.blade.php:5:    <h1>Customer Details</h1>
resources/views/site/my_customer/show.blade.php:12:            <p><strong>Email:</strong> {{ $customer->email }}</p>
resources/views/site/my_customer/show.blade.php:13:            <p><strong>Phone:</strong> {{ $customer->phone }}</p>
resources/views/site/my_customer/show.blade.php:14:            <a href="{{ route('pages.my_customer') }}" class="btn btn-primary">Back to List</a>
resources/views/product_variants/edit.blade.php:4:    <h4 class="mb-3">Sửa biến thể sản phẩm</h4>
resources/views/product_variants/edit.blade.php:9:            <label class="form-label">Sản phẩm</label>
resources/views/product_variants/edit.blade.php:13:                    <option value="{{ $product->id }}" {{ $variant->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
resources/views/product_variants/edit.blade.php:18:            <label class="form-label">SKU</label>
resources/views/product_variants/edit.blade.php:22:            <label class="form-label">Size</label>
resources/views/product_variants/edit.blade.php:26:            <label class="form-label">Chất lượng</label>
resources/views/product_variants/edit.blade.php:30:            <label class="form-label">Ngày sản xuất</label>
resources/views/product_variants/edit.blade.php:34:            <label class="form-label">Tồn kho</label>
resources/views/product_variants/edit.blade.php:38:            <label class="form-label">Hình ảnh</label>
resources/views/product_variants/edit.blade.php:45:            <button type="button" class="btn btn-info" id="btnSelectVariantImageEdit">Chọn ảnh từ thư viện</button>
resources/views/product_variants/edit.blade.php:47:        <button class="btn btn-primary">Cập nhật biến thể</button>
resources/views/product_variants/edit.blade.php:48:        <a href="{{ route('product-variants.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/product_variants/edit.blade.php:62:                    <h5 class='modal-title'>Chọn hình ảnh</h5>
resources/views/product_variants/_variants_table.blade.php:5:            <th>ID</th>
resources/views/product_variants/_variants_table.blade.php:7:            <th>SKU</th>
resources/views/product_variants/_variants_table.blade.php:8:            <th>Sản phẩm</th>
resources/views/product_variants/_variants_table.blade.php:9:            <th>Size</th>
resources/views/product_variants/_variants_table.blade.php:10:            <th>Chất lượng</th>
resources/views/product_variants/_variants_table.blade.php:11:            <th>Ngày SX</th>
resources/views/product_variants/_variants_table.blade.php:12:            <th>Giá bán</th>
resources/views/product_variants/_variants_table.blade.php:13:            <th>Tồn kho</th>
resources/views/product_variants/_variants_table.blade.php:14:            <th>Thao tác</th>
resources/views/product_variants/_variants_table.blade.php:20:            <td><input type="checkbox" class="variant-checkbox" value="{{ $v->id }}"></td>
resources/views/product_variants/_variants_table.blade.php:21:            <td>{{ $v->id }}</td>
resources/views/product_variants/_variants_table.blade.php:27:            <td>{{ $v->sku }}</td>
resources/views/product_variants/_variants_table.blade.php:28:            <td>{{ $v->product->name ?? '' }}</td>
resources/views/product_variants/_variants_table.blade.php:29:            <td>{{ $v->size }}</td>
resources/views/product_variants/_variants_table.blade.php:30:            <td>{{ $v->quality }}</td>
resources/views/product_variants/_variants_table.blade.php:31:            <td>{{ $v->production_date }}</td>
resources/views/product_variants/_variants_table.blade.php:38:            <td>{{ $v->stock }}</td>
resources/views/product_variants/_variants_table.blade.php:40:                <a href="{{ route('product-variants.edit', $v->id) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/product_variants/_variants_table.blade.php:41:                <a href="{{ route('variants.edit-price', $v->id) }}?from=product-variants" class="btn btn-sm btn-info mt-1">Điều chỉnh giá</a>
resources/views/product_variants/_variants_table.blade.php:42:                <button type="button" class="btn btn-sm btn-primary mt-1 clone-variant-index" data-variant-id="{{ $v->id }}" data-variant='@json($v)'>Nhân bản</button>
resources/views/product_variants/_variants_table.blade.php:43:                <button type="button" class="btn btn-sm btn-success mt-1 quick-edit-variant-index" data-variant-id="{{ $v->id }}">Sửa nhanh</button>
resources/views/product_variants/index.blade.php:5:        <h4 class="mb-0">Danh sách biến thể sản phẩm</h4>
resources/views/product_variants/index.blade.php:11:                <input type="text" name="q" class="form-control" placeholder="Tìm kiếm SKU, size, chất lượng, tên sản phẩm..." value="{{ request('q') }}">
resources/views/product_variants/index.blade.php:17:                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
resources/views/product_variants/index.blade.php:28:                <button class="btn btn-primary">Lọc</button>
resources/views/product_variants/index.blade.php:42:                <input type="number" name="min_stock" class="form-control" placeholder="Tồn kho từ" value="{{ request('min_stock') }}">
resources/views/product_variants/index.blade.php:45:                <input type="number" name="max_stock" class="form-control" placeholder="Tồn kho đến" value="{{ request('max_stock') }}">
resources/views/product_variants/index.blade.php:51:        <button class="btn btn-danger" id="bulk-delete-btn">Xoá các mục đã chọn</button>
resources/views/product_variants/index.blade.php:76:                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
resources/views/product_variants/create.blade.php:4:    <h4 class="mb-3">Thêm biến thể sản phẩm</h4>
resources/views/product_variants/create.blade.php:8:            <label class="form-label">Sản phẩm</label>
resources/views/product_variants/create.blade.php:12:                    <option value="{{ $product->id }}">{{ $product->name }}</option>
resources/views/product_variants/create.blade.php:17:            <label class="form-label">SKU</label>
resources/views/product_variants/create.blade.php:21:            <label class="form-label">Size</label>
resources/views/product_variants/create.blade.php:25:            <label class="form-label">Chất lượng</label>
resources/views/product_variants/create.blade.php:29:            <label class="form-label">Ngày sản xuất</label>
resources/views/product_variants/create.blade.php:33:            <label class="form-label">Tồn kho</label>
resources/views/product_variants/create.blade.php:37:            <label class="form-label">Hình ảnh</label>
resources/views/product_variants/create.blade.php:40:            <button type="button" class="btn btn-info" id="btnSelectVariantImageCreate">Chọn ảnh từ thư viện</button>
resources/views/product_variants/create.blade.php:43:            <label class="form-label">Giá bán (VNĐ)</label>
resources/views/product_variants/create.blade.php:46:        <button class="btn btn-primary">Thêm biến thể</button>
resources/views/product_variants/create.blade.php:47:        <a href="{{ route('product-variants.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/product_variants/create.blade.php:59:                                        <h5 class='modal-title'>Chọn hình ảnh</h5>
resources/views/partials/product-search.blade.php:9:                                <input type="text" class="form-control" name="keyword" placeholder="Nhập từ khóa tìm kiếm sản phẩm..." value="{{ request('keyword') }}">
resources/views/partials/product-search.blade.php:12:                                <button type="submit" class="site-btn btn-sm">Tìm kiếm</button>
resources/views/layouts/sidebarGallery.blade.php:37:								<span>Gallery</span>
resources/views/layouts/partials/site_header.blade.php:14:        <li><i class="fa fa-envelope-o"></i> email@gmail.com</li>
resources/views/layouts/partials/site_header.blade.php:33:                        <li><i class="fa fa-clock-o"></i>{{ $settings['slogan']->value ?? __('site.slogan_fallback') }}</li>
resources/views/layouts/partials/site_header.blade.php:34:                        <li><i class="fa fa-phone"></i>  {{ $settings['HOTLINE']->value ?? '0909 990 909' }}</li>
resources/views/layouts/partials/site_header.blade.php:67:                            <h2>{{ $settings['brand_name']->value ?? __('site.logo_fallback') }}</h2>
resources/views/layouts/partials/site_footer.blade.php:23:                                <li class="color-white">{{ __('site.tax_code') }}: {{ $settings['tax_number']->value ?? __('site.not_available') }}</li>
resources/views/layouts/partials/site_footer.blade.php:24:                                <li class="color-white">{{ __('site.address') }}: {{ $settings['address']->value ?? '' }}</li>
resources/views/layouts/partials/site_footer.blade.php:25:                                <li class="color-white">{{ __('site.hotline') }}: {{ $settings['hotline']->value ?? '' }}</li>
resources/views/layouts/partials/site_footer.blade.php:26:                                <li class="color-white">{{ __('site.email') }}: {{ $settings['email']->value ?? '' }}</li>
resources/views/layouts/partials/site_footer.blade.php:48:                <p>Copyright ©<script>document.write(new Date().getFullYear());</script> {{ __('site.copyright') }}</p>
resources/views/layouts/admin.blade.php:61:                                    <strong>Thong bao</strong>
resources/views/layouts/admin.blade.php:62:                                    <a href="{{ route('admin.notifications.index') }}" class="small">Xem tat ca</a>
resources/views/layouts/admin.blade.php:68:                                            <div class="fw-semibold">{{ $notification->data['title'] ?? 'Thong bao' }}</div>
resources/views/layouts/admin.blade.php:69:                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '-', 80) }}</div>
resources/views/layouts/admin.blade.php:70:                                            <div class="small text-muted">{{ optional($notification->created_at)->diffForHumans() }}</div>
resources/views/layouts/admin.blade.php:74:                                    <div class="p-3 text-muted text-center">Chua co thong bao</div>
resources/views/layouts/admin.blade.php:77:                                    <a href="{{ route('admin.events.index') }}" class="small">Xem nhat ky su kien</a>
resources/views/layouts/admin.blade.php:139:                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
resources/views/layouts/mediasidebar.blade.php:37:								<span>Media</span>
resources/views/layouts/sidebar.blade.php:36:								<div class="fs-sm text-white opacity-75 mb-1">{{ auth()->user()->roles()->first()->name ?? '' }}</div>
resources/views/layouts/sidebar.blade.php:37:								<div class="fw-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
resources/views/layouts/sidebar.blade.php:48:							<div class="font-semibold">{{ auth()->user()->name }}</div>
resources/views/layouts/sidebar.blade.php:49:							<div class="text-sm text-gray-400">{{ auth()->user()->email }}</div>
resources/views/layouts/sidebar.blade.php:59:                        <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm mt-2">Profile</a>
resources/views/layouts/sidebar.blade.php:82:							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Main</div>
resources/views/layouts/sidebar.blade.php:90:									<span class="d-block fw-normal opacity-50">No pending orders</span>
resources/views/layouts/sidebar.blade.php:133:								<span>Quan tri user & gan kho</span>
resources/views/layouts/sidebar.blade.php:139:								<span>Quan tri kho hang</span>
resources/views/layouts/sidebar.blade.php:145:								<span>Nhap xuat kho hang</span>
resources/views/layouts/sidebar.blade.php:151:								<span>Bao cao ton kho</span>
resources/views/layouts/sidebar.blade.php:214:								<span>Teams</span>
resources/views/layouts/sidebar.blade.php:220:								<span>Tạo quy trình</span>
resources/views/layouts/app.blade.php:86:                                                <div class="fw-semibold">{{ $notification->data['title'] ?? __('common.notifications.title') }}</div>
resources/views/layouts/app.blade.php:87:                                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '-', 80) }}</div>
resources/views/layouts/app.blade.php:88:                                                <div class="small text-muted">{{ optional($notification->created_at)->diffForHumans() }}</div>
resources/views/users/bulk_assign_team.blade.php:6:        <h2 class="mb-0">Gán hàng loạt user vào team</h2>
resources/views/users/bulk_assign_team.blade.php:7:        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Quay lại danh sách user</a>
resources/views/users/bulk_assign_team.blade.php:18:                    <label class="form-label">Tìm user</label>
resources/views/users/bulk_assign_team.blade.php:19:                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Tên hoặc email">
resources/views/users/bulk_assign_team.blade.php:22:                    <label class="form-label">Lọc theo role</label>
resources/views/users/bulk_assign_team.blade.php:24:                        <option value="">Tất cả role</option>
resources/views/users/bulk_assign_team.blade.php:26:                            <option value="{{ $role->id }}" {{ (string) request('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
resources/views/users/bulk_assign_team.blade.php:31:                    <label class="form-label">Lọc theo team</label>
resources/views/users/bulk_assign_team.blade.php:33:                        <option value="">Tất cả team</option>
resources/views/users/bulk_assign_team.blade.php:35:                            <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
resources/views/users/bulk_assign_team.blade.php:40:                    <button class="btn btn-primary">Lọc</button>
resources/views/users/bulk_assign_team.blade.php:52:                    <label class="form-label">Team cần gán</label>
resources/views/users/bulk_assign_team.blade.php:56:                            <option value="{{ $team->id }}">{{ $team->name }}</option>
resources/views/users/bulk_assign_team.blade.php:61:                    <button type="submit" class="btn btn-success" onclick="return confirm('Xác nhận cập nhật team cho các user đã chọn?')">Cập nhật team cho user đã chọn</button>
resources/views/users/bulk_assign_team.blade.php:71:                        <th>ID</th>
resources/views/users/bulk_assign_team.blade.php:72:                        <th>Tên</th>
resources/views/users/bulk_assign_team.blade.php:73:                        <th>Email</th>
resources/views/users/bulk_assign_team.blade.php:74:                        <th>Role</th>
resources/views/users/bulk_assign_team.blade.php:75:                        <th>Team hiện tại</th>
resources/views/users/bulk_assign_team.blade.php:81:                            <td><input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="row-check"></td>
resources/views/users/bulk_assign_team.blade.php:82:                            <td>{{ $user->id }}</td>
resources/views/users/bulk_assign_team.blade.php:83:                            <td>{{ $user->name }}</td>
resources/views/users/bulk_assign_team.blade.php:84:                            <td>{{ $user->email }}</td>
resources/views/users/bulk_assign_team.blade.php:87:                                    <span class="badge bg-info">{{ $role->name }}</span>
resources/views/users/bulk_assign_team.blade.php:90:                            <td>{{ $user->team->name ?? 'Chưa gán' }}</td>
resources/views/users/bulk_assign_team.blade.php:94:                            <td colspan="6" class="text-center">Không có user phù hợp.</td>
resources/views/users/edit.blade.php:5:    <h2>Sửa User</h2>
resources/views/users/edit.blade.php:12:            <label class="form-label">Tên</label>
resources/views/users/edit.blade.php:17:            <label class="form-label">Email</label>
resources/views/users/edit.blade.php:22:            <label class="form-label">Mật khẩu (để trống nếu không đổi)</label>
resources/views/users/edit.blade.php:27:            <label class="form-label">Xác nhận mật khẩu</label>
resources/views/users/edit.blade.php:32:            <label class="form-label">Vai trò</label>
resources/views/users/edit.blade.php:45:            <label class="form-label">Team</label>
resources/views/users/edit.blade.php:54:            <small class="text-muted">Leader/Manager sẽ xem đơn theo team được gán.</small>
resources/views/users/edit.blade.php:58:            <label class="form-label">Kho duoc assign</label>
resources/views/users/edit.blade.php:67:            <small class="text-muted">User role warehouse se chi thao tac tren kho duoc gan.</small>
resources/views/users/edit.blade.php:70:        <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/users/edit.blade.php:71:        <a href="{{ route('users.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/users/index.blade.php:6:        <h2 class="mb-0">Danh sách Người dùng</h2>
resources/views/users/index.blade.php:8:            <a href="{{ route('users.bulk-assign-team.form') }}" class="btn btn-outline-primary">Gán hàng loạt vào team</a>
resources/views/users/index.blade.php:21:                    <label class="form-label">Tìm kiếm</label>
resources/views/users/index.blade.php:22:                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Tên hoặc email">
resources/views/users/index.blade.php:25:                    <label class="form-label">Team</label>
resources/views/users/index.blade.php:27:                        <option value="">Tất cả team</option>
resources/views/users/index.blade.php:29:                            <option value="{{ $team->id }}" {{ (string) request('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
resources/views/users/index.blade.php:34:                    <label class="form-label">Role</label>
resources/views/users/index.blade.php:36:                        <option value="">Tất cả role</option>
resources/views/users/index.blade.php:38:                            <option value="{{ $role->id }}" {{ (string) request('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
resources/views/users/index.blade.php:43:                    <button class="btn btn-primary">Lọc</button>
resources/views/users/index.blade.php:44:                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Reset</a>
resources/views/users/index.blade.php:53:                <th>ID</th>
resources/views/users/index.blade.php:54:                <th>Tên</th>
resources/views/users/index.blade.php:55:                <th>Email</th>
resources/views/users/index.blade.php:56:                <th>Team</th>
resources/views/users/index.blade.php:57:                <th>Kho được assign</th>
resources/views/users/index.blade.php:58:                <th>Quyền</th>
resources/views/users/index.blade.php:59:                <th>Hành động</th>
resources/views/users/index.blade.php:65:                <td>{{ $user->id }}</td>
resources/views/users/index.blade.php:66:                <td>{{ $user->name }}</td>
resources/views/users/index.blade.php:67:                <td>{{ $user->email }}</td>
resources/views/users/index.blade.php:68:                <td>{{ $user->team->name ?? 'Chưa gán' }}</td>
resources/views/users/index.blade.php:69:                <td>{{ $user->warehouse->name ?? 'Chưa gán' }}</td>
resources/views/users/index.blade.php:72:                        <span class="badge bg-info">{{ $role->name }}</span>
resources/views/users/index.blade.php:76:                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">Sửa</a>
resources/views/users/create.blade.php:5:    <h2>Thêm User mới</h2>
resources/views/users/create.blade.php:10:            <label class="form-label">Tên</label>
resources/views/users/create.blade.php:15:            <label class="form-label">Email</label>
resources/views/users/create.blade.php:20:            <label class="form-label">Mật khẩu</label>
resources/views/users/create.blade.php:25:            <label class="form-label">Xác nhận mật khẩu</label>
resources/views/users/create.blade.php:30:            <label class="form-label">Vai trò</label>
resources/views/users/create.blade.php:41:            <label class="form-label">Team</label>
resources/views/users/create.blade.php:45:                    <option value="{{ $team->id }}">{{ $team->name }}</option>
resources/views/users/create.blade.php:48:            <small class="text-muted">Leader/Manager sẽ xem đơn theo team được gán.</small>
resources/views/users/create.blade.php:52:            <label class="form-label">Kho duoc assign</label>
resources/views/users/create.blade.php:56:                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
resources/views/users/create.blade.php:59:            <small class="text-muted">User role warehouse se chi thao tac tren kho duoc gan.</small>
resources/views/users/create.blade.php:62:        <button type="submit" class="btn btn-success">Tạo</button>
resources/views/users/create.blade.php:63:        <a href="{{ route('users.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/inventory-reservations/index.blade.php:5:    <h1>Inventory Reservations</h1>
resources/views/inventory-reservations/index.blade.php:9:                <th>ID</th>
resources/views/inventory-reservations/index.blade.php:10:                <th>Order Item ID</th>
resources/views/inventory-reservations/index.blade.php:11:                <th>Inventory ID</th>
resources/views/inventory-reservations/index.blade.php:12:                <th>Quantity</th>
resources/views/inventory-reservations/index.blade.php:13:                <th>Reserved At</th>
resources/views/inventory-reservations/index.blade.php:19:                <td>{{ $reservation->id }}</td>
resources/views/inventory-reservations/index.blade.php:20:                <td>{{ $reservation->order_item_id }}</td>
resources/views/inventory-reservations/index.blade.php:21:                <td>{{ $reservation->inventory_id }}</td>
resources/views/inventory-reservations/index.blade.php:22:                <td>{{ $reservation->quantity }}</td>
resources/views/inventory-reservations/index.blade.php:23:                <td>{{ $reservation->reserved_at }}</td>
resources/views/companies/edit.blade.php:5:    <h2>Sửa thông tin: {{ $company->name }}</h2>
resources/views/companies/edit.blade.php:12:            <button class="btn btn-primary">Cập nhật</button>
resources/views/companies/edit.blade.php:13:            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/companies/_form.blade.php:3:        <label class="form-label">Tên công ty</label>
resources/views/companies/_form.blade.php:9:        <label class="form-label">Mã số thuế</label>
resources/views/companies/_form.blade.php:15:        <label class="form-label">Phone</label>
resources/views/companies/_form.blade.php:21:        <label class="form-label">Email</label>
resources/views/companies/_form.blade.php:28:        <textarea name="address" class="form-control" rows="3">{{ old('address', $company->address ?? '') }}</textarea>
resources/views/companies/_form.blade.php:33:        <label class="form-label">Ghi chú</label>
resources/views/companies/_form.blade.php:34:        <textarea name="note" class="form-control" rows="3">{{ old('note', $company->note ?? '') }}</textarea>
resources/views/companies/import.blade.php:4:    <h2>Import công ty từ Excel</h2>
resources/views/companies/import.blade.php:6:        <b>Hướng dẫn file Excel import:</b><br>
resources/views/companies/import.blade.php:7:        - Hàng đầu tiên phải có các cột: <b>name</b> (bắt buộc), <b>mst</b>, <b>phone</b>, <b>email</b>, <b>address</b>, <b>note</b>.<br>
resources/views/companies/import.blade.php:8:        - Cột <b>phone</b> nên để dạng chuỗi, ví dụ: '0123456789'.<br>
resources/views/companies/import.blade.php:10:        <a href="/sample/company_import_sample.xlsx" target="_blank">Tải file mẫu</a>
resources/views/companies/import.blade.php:15:        <button class="btn btn-primary">Import</button>
resources/views/companies/import.blade.php:22:            <strong>Các dòng lỗi khi import:</strong>
resources/views/companies/import.blade.php:26:                        <b>Dòng:</b> {{ $err['row'] }} | <b>Cột:</b> {{ $err['attribute'] }}<br>
resources/views/companies/import.blade.php:27:                        <b>Lỗi:</b> {{ implode('; ', $err['errors']) }}<br>
resources/views/companies/import.blade.php:28:                        <b>Giá trị:</b> {{ json_encode($err['values']) }}
resources/views/companies/import.blade.php:36:            <strong>Kết quả import từng dòng:</strong>
resources/views/companies/import.blade.php:44:                            <b>Lỗi:</b> {{ $rec['error'] ?? '' }}
resources/views/companies/import.blade.php:51:    <a href="{{ route('companies.index') }}" class="btn btn-secondary mt-2">Quay lại danh sách công ty</a>
resources/views/companies/index.blade.php:5:    <h2>Danh sách công ty</h2>
resources/views/companies/index.blade.php:8:        <a href="{{ route('companies.import.form') }}" class="btn btn-warning">Import Excel</a>
resources/views/companies/index.blade.php:9:        <a href="{{ route('companies.export') }}" class="btn btn-info">Export Excel</a>
resources/views/companies/index.blade.php:14:                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm tên / SĐT / Email / MST">
resources/views/companies/index.blade.php:36:                <button class="btn btn-primary">Lọc</button>
resources/views/companies/index.blade.php:37:                <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">Reset</a>
resources/views/companies/index.blade.php:53:                <th>Tên công ty</th>
resources/views/companies/index.blade.php:54:                <th>Mã số thuế</th>
resources/views/companies/index.blade.php:55:                <th>Phone</th>
resources/views/companies/index.blade.php:56:                <th>Email</th>
resources/views/companies/index.blade.php:57:                <th>Nhân viên</th>
resources/views/companies/index.blade.php:59:                <th>Hành động</th>
resources/views/companies/index.blade.php:65:                    <td>{{ $company->id }}</td>
resources/views/companies/index.blade.php:66:                    <td>{{ $company->name }}</td>
resources/views/companies/index.blade.php:67:                    <td>{{ $company->mst }}</td>
resources/views/companies/index.blade.php:68:                    <td>{{ $company->phone }}</td>
resources/views/companies/index.blade.php:69:                    <td>{{ $company->email }}</td>
resources/views/companies/index.blade.php:70:                    <td>{{ optional($company->assignedTo)->name ?? '-' }}</td>
resources/views/companies/index.blade.php:71:                    <td>{{ $company->address }}</td>
resources/views/companies/index.blade.php:73:                        <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/companies/index.blade.php:76:                            <button class="btn btn-sm btn-danger">Xóa</button>
resources/views/companies/index.blade.php:82:                    <td colspan="8" class="text-center">Chưa có công ty</td>
resources/views/companies/create.blade.php:5:    <h2>Thêm công ty mới</h2>
resources/views/companies/create.blade.php:11:            <button class="btn btn-success">Lưu</button>
resources/views/companies/create.blade.php:12:            <a href="{{ route('companies.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/categories/edit.blade.php:5:    <h2>Chỉnh sửa danh mục</h2>
resources/views/categories/edit.blade.php:10:            <label for="name">Tên danh mục:</label>
resources/views/categories/edit.blade.php:14:            <label for="image">Hình ảnh:</label>
resources/views/categories/edit.blade.php:20:        <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/categories/edit.blade.php:21:        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/categories/index.blade.php:5:    <h2>Quản lý danh mục</h2>
resources/views/categories/index.blade.php:8:        <a href="{{ route('categories.create') }}" class="btn btn-success mb-3">Thêm danh mục mới</a>
resources/views/categories/index.blade.php:18:                <th>ID</th>
resources/views/categories/index.blade.php:19:                <th>Tên danh mục</th>
resources/views/categories/index.blade.php:20:                <th>Hành động</th>
resources/views/categories/index.blade.php:26:                <td>{{ $category->id }}</td>
resources/views/categories/index.blade.php:27:                <td>{{ $category->name }}</td>                
resources/views/categories/index.blade.php:30:                        <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-primary btn-sm">Sửa</a>
resources/views/categories/index.blade.php:36:                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa không?')">Xóa</button>
resources/views/categories/create.blade.php:5:    <h2>Thêm danh mục mới</h2>
resources/views/categories/create.blade.php:9:            <label for="name">Tên danh mục:</label>
resources/views/categories/create.blade.php:13:            <label for="image">Hình ảnh:</label>
resources/views/categories/create.blade.php:16:        <button type="submit" class="btn btn-primary">Thêm danh mục</button>
resources/views/categories/create.blade.php:17:        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/media/quickpopup.blade.php:11:                    <p class="card-text">{{ $m->file_name }}</p>
resources/views/media/_list.blade.php:8:                    <small class="d-block text-truncate">{{ $image->file_name }}</small>
resources/views/media/_list.blade.php:10:                        <a href="{{ route('media.edit', $image->id) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i> Edit</a>
resources/views/media/_list.blade.php:14:                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
resources/views/media/browse.blade.php:4:    <title>Chọn hình ảnh</title>
resources/views/media/browse.blade.php:30:<h3>Chọn hình ảnh</h3>
resources/views/media/browse.blade.php:38:            <div class="file-name">{{ $file->file_name }}</div>
resources/views/media/gallery.blade.php:19:    <h2>Thư viện Media</h2>
resources/views/media/gallery.blade.php:22:            <a href="#" class="btn btn-primary mb-3">Thêm Media</a>
resources/views/media/gallery.blade.php:27:            <h2>Upload media mới</h2>
resources/views/media/gallery.blade.php:31:                <button type="submit" class="btn btn-primary">Upload</button>
resources/views/media/edit.blade.php:5:    <h4>Edit Media</h4>
resources/views/media/edit.blade.php:12:            <label for="file_name" class="form-label">Tên hiển thị</label>
resources/views/media/edit.blade.php:17:            <label class="form-label">File hiện tại</label><br>
resources/views/media/edit.blade.php:22:            <label for="file" class="form-label">Thay thế file (nếu cần)</label>
resources/views/media/edit.blade.php:26:        <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/media/edit.blade.php:27:        <a href="{{ route('media.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/media/popup.blade.php:5:    <h2>Thư viện Media</h2>
resources/views/media/popup.blade.php:8:            <a href="#" class="btn btn-primary mb-3">Thêm Media</a>
resources/views/media/popup.blade.php:13:            <h2>Upload media mới</h2>
resources/views/media/popup.blade.php:17:                <button type="submit" class="btn btn-primary">Upload</button>
resources/views/media/index.blade.php:5:    <h2>Thư viện ảnh của {{ class_basename($model) }} #{{ $model->id }}</h2>
resources/views/media/index.blade.php:11:        <button type="submit" class="btn btn-primary">Upload</button>
resources/views/media/index.blade.php:25:                    <p class="mb-1 text-truncate">{{ $item->file_name }}</p>
resources/views/media/index.blade.php:58:            <h5 class="modal-title">Thông tin hình ảnh</h5>
resources/views/media/index.blade.php:64:                <li class="list-group-item"><strong>Tên file:</strong> <span id="media-file-name"></span></li>
resources/views/media/index.blade.php:65:                <li class="list-group-item"><strong>Kích thước:</strong> <span id="media-file-size"></span></li>
resources/views/media/index.blade.php:66:                <li class="list-group-item"><strong>Loại file:</strong> <span id="media-mime-type"></span></li>
resources/views/media/index.blade.php:68:                <li class="list-group-item"><strong>Ngày tạo:</strong> <span id="media-created-at"></span></li>
resources/views/media/index.blade.php:73:            <button id="detachMedia" class="btn btn-warning">Xoá liên kết</button>
resources/views/media/index.blade.php:74:            <button id="deleteMedia" class="btn btn-danger">Xoá hoàn toàn</button>
resources/views/media/quick-select.blade.php:7:                <p class="card-text">{{ $m->file_name }}</p>
resources/views/media/create.blade.php:5:    <h2>Upload media mới</h2>
resources/views/media/create.blade.php:9:        <button type="submit" class="btn btn-primary">Upload</button>
resources/views/media/library.blade.php:5:    <h2>Thư viện Media</h2>
resources/views/media/library.blade.php:8:            <a href="{{ route('media.create') }}" class="btn btn-primary mb-3">Thêm Media</a>
resources/views/media/library.blade.php:22:                    <p class="mb-1 text-truncate">{{ $item->file_name }}</p>
resources/views/media/library.blade.php:60:            <h5 class="modal-title">Chi tiết Media</h5>
resources/views/media/library.blade.php:66:                <li class="list-group-item"><strong>Tên file:</strong> <span id="media-file-name"></span></li>
resources/views/media/library.blade.php:67:                <li class="list-group-item"><strong>Kích thước:</strong> <span id="media-file-size"></span></li>
resources/views/media/library.blade.php:68:                <li class="list-group-item"><strong>Loại file:</strong> <span id="media-mime-type"></span></li>
resources/views/media/library.blade.php:70:                <li class="list-group-item"><strong>Ngày tạo:</strong> <span id="media-created-at"></span></li>
resources/views/media/library.blade.php:75:            <button id="deleteMedia" class="btn btn-danger">Xoá hoàn toàn</button>
resources/views/approval_workflows/edit.blade.php:5:    <h4 class="mb-3">Sửa quy trình xét duyệt</h4>
resources/views/approval_workflows/edit.blade.php:13:                    <label class="form-label">Mã quy trình</label>
resources/views/approval_workflows/edit.blade.php:14:                    <input type="text" name="code" class="form-control" value="{{ old('code', $approvalWorkflow->code) }}" placeholder="order_default">
resources/views/approval_workflows/edit.blade.php:17:                    <label class="form-label">Tên quy trình</label>
resources/views/approval_workflows/edit.blade.php:18:                    <input type="text" name="name" class="form-control" value="{{ old('name', $approvalWorkflow->name) }}" placeholder="Quy trình duyệt Sale -> Manager -> Director">
resources/views/approval_workflows/edit.blade.php:23:                        <label class="form-check-label" for="is_active">Kích hoạt</label>
resources/views/approval_workflows/edit.blade.php:28:            <h6 class="mt-2">Các bước duyệt</h6>
resources/views/approval_workflows/edit.blade.php:29:            <p class="text-muted mb-2">Chọn role theo thứ tự duyệt.</p>
resources/views/approval_workflows/edit.blade.php:56:                                <label class="form-check-label" for="can_skip_{{ $idx }}">Cho phép bỏ qua bước</label>
resources/views/approval_workflows/edit.blade.php:58:                            <button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-step">Xóa</button>
resources/views/approval_workflows/edit.blade.php:68:            <button type="submit" class="btn btn-primary">Cập nhật quy trình</button>
resources/views/approval_workflows/edit.blade.php:69:            <a href="{{ route('approval-workflows.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/approval_workflows/edit.blade.php:125:                            <label class="form-check-label" for="can_skip_${idx}">Cho phep bo qua buoc</label>
resources/views/approval_workflows/edit.blade.php:127:                        <button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-step">Xoa</button>
resources/views/approval_workflows/index.blade.php:6:        <h4 class="mb-0">Quy trình xét duyệt</h4>
resources/views/approval_workflows/index.blade.php:7:        <a href="{{ route('approval-workflows.create') }}" class="btn btn-primary">Tạo quy trình</a>
resources/views/approval_workflows/index.blade.php:16:                            <th>ID</th>
resources/views/approval_workflows/index.blade.php:17:                            <th>Mã quy trình</th>
resources/views/approval_workflows/index.blade.php:18:                            <th>Tên quy trình</th>
resources/views/approval_workflows/index.blade.php:19:                            <th>Trạng thái</th>
resources/views/approval_workflows/index.blade.php:20:                            <th>Các bước</th>
resources/views/approval_workflows/index.blade.php:21:                            <th>Ngày tạo</th>
resources/views/approval_workflows/index.blade.php:22:                            <th>Thao tác</th>
resources/views/approval_workflows/index.blade.php:28:                                <td>{{ $workflow->id }}</td>
resources/views/approval_workflows/index.blade.php:29:                                <td>{{ $workflow->code }}</td>
resources/views/approval_workflows/index.blade.php:30:                                <td>{{ $workflow->name }}</td>
resources/views/approval_workflows/index.blade.php:35:                                        <span class="badge bg-secondary">Không hoạt động</span>
resources/views/approval_workflows/index.blade.php:45:                                <td>{{ $workflow->created_at }}</td>
resources/views/approval_workflows/index.blade.php:47:                                    <a href="{{ route('approval-workflows.edit', $workflow) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/approval_workflows/index.blade.php:52:                                <td colspan="7" class="text-center">Chưa có quy trình nào.</td>
resources/views/approval_workflows/create.blade.php:5:    <h4 class="mb-3">Tạo quy trình xét duyệt</h4>
resources/views/approval_workflows/create.blade.php:12:                    <label class="form-label">Mã quy trình</label>
resources/views/approval_workflows/create.blade.php:13:                    <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="order_default">
resources/views/approval_workflows/create.blade.php:16:                    <label class="form-label">Tên quy trình</label>
resources/views/approval_workflows/create.blade.php:17:                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Quy trình duyệt Sale -> Manager -> Director">
resources/views/approval_workflows/create.blade.php:22:                        <label class="form-check-label" for="is_active">Kích hoạt</label>
resources/views/approval_workflows/create.blade.php:27:            <h6 class="mt-2">Các bước duyệt</h6>
resources/views/approval_workflows/create.blade.php:28:            <p class="text-muted mb-2">Chọn role theo thứ tự duyệt. Ví dụ: sale -> manager -> director.</p>
resources/views/approval_workflows/create.blade.php:53:                                <label class="form-check-label" for="can_skip_{{ $idx }}">Cho phép bỏ qua bước</label>
resources/views/approval_workflows/create.blade.php:55:                            <button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-step">Xóa</button>
resources/views/approval_workflows/create.blade.php:65:            <button type="submit" class="btn btn-primary">Lưu quy trình</button>
resources/views/approval_workflows/create.blade.php:66:            <a href="{{ route('approval-workflows.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/approval_workflows/create.blade.php:122:                            <label class="form-check-label" for="can_skip_${idx}">Cho phep bo qua buoc</label>
resources/views/approval_workflows/create.blade.php:124:                        <button type="button" class="btn btn-sm btn-outline-danger ms-auto remove-step">Xoa</button>
resources/views/posts/index.blade.php:8:                        <h2>Tin tức</h2>
resources/views/posts/index.blade.php:10:                            <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
resources/views/posts/index.blade.php:11:                            <span> Tin tức</span>
resources/views/posts/index.blade.php:32:                                <div class="latest__blog__item__pic set-bg" data-setbg="{{ asset('storage/' . $post->image) }}" style="background-image: url('{{ asset('storage/' . $post->image) }}');"></div>
resources/views/posts/index.blade.php:37:                                <h5>{{ $post->title }}</h5>
resources/views/posts/index.blade.php:38:                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->content), 120) }}</p>
resources/views/posts/index.blade.php:39:                                <a href="{{ route('posts.show', $post) }}">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
resources/views/posts/index.blade.php:50:                    <li class="list-group-item">Chuyên mục</li>
resources/views/posts/index.blade.php:53:                            <a href="{{ route('posts.category', $category) }}">{{ $category->name }}</a>
resources/views/posts/index.blade.php:59:                    <div class="card-header">Thẻ</div>
resources/views/posts/index.blade.php:62:                            <a href="#" class="btn btn-sm btn-secondary mb-1">{{ $tag->name }}</a>
resources/views/posts/category.blade.php:5:        <h1>{{ $category->name }}</h1>
resources/views/posts/category.blade.php:11:                            <h5 class="card-title">{{ $post->title }}</h5>
resources/views/posts/category.blade.php:12:                            <p class="card-text">{{ Str::limit($post->content, 150) }}</p>
resources/views/posts/category.blade.php:13:                            <a href="{{ route('posts.show', $post) }}" class="btn btn-primary">Read More</a>
resources/views/posts/category.blade.php:21:                    <div class="card-header">Categories</div>
resources/views/posts/category.blade.php:26:                                    <a href="{{ route('posts.category', $category) }}">{{ $category->name }}</a>
resources/views/posts/category.blade.php:33:                    <div class="card-header">Tags</div>
resources/views/posts/category.blade.php:36:                            <a href="#" class="btn btn-sm btn-secondary mb-1">{{ $tag->name }}</a>
resources/views/posts/show.blade.php:51:                        <h2>Tin tức</h2>
resources/views/posts/show.blade.php:53:                            <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
resources/views/posts/show.blade.php:54:                            <a href="{{ route('posts.list') }}"><i class="fa fa-home"></i> Tin tức</a>
resources/views/posts/show.blade.php:55:                            <span> {{ $post->title }}</span>
resources/views/posts/show.blade.php:73:                    <h1 class="mb-3">{{ $post->title }}</h1>
resources/views/posts/show.blade.php:76:                        <span><i class="fa fa-folder-open-o"></i> {{ $post->category->name ?? 'Chưa phân loại' }}</span>
resources/views/posts/show.blade.php:77:                        <span><i class="fa fa-calendar"></i> {{ optional($post->created_at)->format('d/m/Y H:i') }}</span>
resources/views/posts/show.blade.php:78:                        <span><i class="fa fa-user"></i> {{ $post->author->name ?? 'Admin' }}</span>
resources/views/posts/show.blade.php:88:                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', $post)) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-facebook"></i> Facebook</a>
resources/views/posts/show.blade.php:89:                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('posts.show', $post)) }}&text={{ urlencode($post->title) }}" target="_blank" class="btn btn-info btn-sm"><i class="fa fa-twitter"></i> Twitter</a>
resources/views/posts/show.blade.php:90:                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(route('posts.show', $post)) }}&title={{ urlencode($post->title) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-linkedin"></i> LinkedIn</a>
resources/views/posts/show.blade.php:91:                    <a href="https://zalo.me/share?url={{ urlencode(route('posts.show', $post)) }}" target="_blank" class="btn btn-info btn-sm">Zalo</a>
resources/views/posts/show.blade.php:96:                        <span>Bản tin hàng ngày</span>
resources/views/posts/show.blade.php:97:                        <h3 class="mb-0">Tin tức khác</h3>
resources/views/posts/show.blade.php:104:                                        <div class="latest__blog__item__pic set-bg" data-setbg="{{ asset('storage/' . $otherPost->image) }}" style="background-image: url('{{ asset('storage/' . $otherPost->image) }}');"></div>
resources/views/posts/show.blade.php:109:                                        <h5>{{ $otherPost->title }}</h5>
resources/views/posts/show.blade.php:110:                                        <p class="mb-2"><small class="text-muted"><i class="fa fa-calendar"></i> {{ optional($otherPost->created_at)->format('d/m/Y') }} | <i class="fa fa-folder-open-o"></i> {{ $otherPost->category->name ?? 'Tin tức' }} | <i class="fa fa-user"></i> {{ $otherPost->author->name ?? 'Admin' }}</small></p>
resources/views/posts/show.blade.php:111:                                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($otherPost->excerpt ?: $otherPost->content), 120) }}</p>
resources/views/posts/show.blade.php:112:                                        <a href="{{ route('posts.show', $otherPost) }}">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
resources/views/posts/show.blade.php:125:                    <div class="card-header">Chuyên mục</div>
resources/views/posts/show.blade.php:130:                                    <a href="{{ route('posts.category', $category) }}">{{ $category->name }}</a>
resources/views/posts/show.blade.php:131:                                    <span class="badge bg-primary rounded-pill">{{ $category->posts_count }}</span>
resources/views/posts/show.blade.php:138:                    <div class="card-header">Thẻ</div>
resources/views/posts/show.blade.php:141:                            <a href="#" class="btn btn-sm btn-secondary mb-1">{{ $tag->name }}</a>
resources/views/permissions/edit.blade.php:5:    <h2>Sửa Permission: {{ $permission->name }}</h2>
resources/views/permissions/edit.blade.php:11:            <label for="name" class="form-label">Tên Permission</label>
resources/views/permissions/edit.blade.php:15:            <label for="description" class="form-label">Mô tả</label>
resources/views/permissions/edit.blade.php:19:            <label for="group" class="form-label">Nhóm</label>
resources/views/permissions/edit.blade.php:24:        <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/permissions/edit.blade.php:25:        <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/permissions/index.blade.php:5:    <h2>Danh sách quyền</h2>
resources/views/permissions/index.blade.php:10:                <th>ID</th>
resources/views/permissions/index.blade.php:11:                <th>Tên quyền</th>
resources/views/permissions/index.blade.php:12:                <th>Mô tả</th>
resources/views/permissions/index.blade.php:13:                <th>Thao tác</th>
resources/views/permissions/index.blade.php:19:                <td>{{ $p->id }}</td>
resources/views/permissions/index.blade.php:20:                <td>{{ $p->name }}</td>
resources/views/permissions/index.blade.php:21:                <td>{{ $p->description }}</td>
resources/views/permissions/index.blade.php:23:                    <a href="{{ route('permissions.edit', $p->id) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/permissions/index.blade.php:26:                        <button onclick="return confirm('Xóa quyền này?')" class="btn btn-sm btn-danger">Xóa</button>
resources/views/permissions/create.blade.php:5:    <h2>Thêm Permission mới</h2>
resources/views/permissions/create.blade.php:10:            <label for="name" class="form-label">Tên Permission</label>
resources/views/permissions/create.blade.php:14:            <label for="description" class="form-label">Mô tả</label>
resources/views/permissions/create.blade.php:18:            <label for="group" class="form-label">Nhóm</label>
resources/views/permissions/create.blade.php:21:        <button type="submit" class="btn btn-primary">Lưu</button>
resources/views/permissions/create.blade.php:22:        <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/inventories/edit.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Edit Inventory Record</h1>
resources/views/inventories/edit.blade.php:13:            <h6 class="m-0 font-weight-bold text-primary">Edit Inventory Details</h6>
resources/views/inventories/_form.blade.php:2:    <label for="product_variant_id" class="form-label">Product Variant</label>
resources/views/inventories/_form.blade.php:4:        <option value="">Select a Variant</option>
resources/views/inventories/_form.blade.php:17:    <label for="warehouse_id" class="form-label">Warehouse</label>
resources/views/inventories/_form.blade.php:19:        <option value="">Select a Warehouse</option>
resources/views/inventories/_form.blade.php:32:    <label for="quantity" class="form-label">Quantity</label>
resources/views/inventories/_form.blade.php:40:    <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
resources/views/inventories/_form.blade.php:47:<button type="submit" class="btn btn-primary">Submit</button>
resources/views/inventories/_form.blade.php:48:<a href="{{ route('inventories.index') }}" class="btn btn-secondary">Cancel</a>
resources/views/inventories/index.blade.php:5:    <h1>Thong ke kho hang</h1>
resources/views/inventories/index.blade.php:8:        <div class="card-header">Bo loc thong ke</div>
resources/views/inventories/index.blade.php:12:                    <label class="form-label">Kho</label>
resources/views/inventories/index.blade.php:14:                        <option value="">Tat ca kho</option>
resources/views/inventories/index.blade.php:23:                    <label class="form-label">Ngay thong ke</label>
resources/views/inventories/index.blade.php:27:                    <label class="form-label">Khoang nhanh</label>
resources/views/inventories/index.blade.php:31:                        <option value="custom" {{ $rangeStats['range_preset'] === 'custom' ? 'selected' : '' }}>Tu chon</option>
resources/views/inventories/index.blade.php:35:                    <label class="form-label">Tu ngay</label>
resources/views/inventories/index.blade.php:39:                    <label class="form-label">Den ngay</label>
resources/views/inventories/index.blade.php:43:                    <button type="submit" class="btn btn-primary">Xem thong ke</button>
resources/views/inventories/index.blade.php:44:                    <a href="{{ route('inventories.index') }}" class="btn btn-secondary">Reset</a>
resources/views/inventories/index.blade.php:53:                <div class="card-header">Tong ton hien tai</div>
resources/views/inventories/index.blade.php:55:                    <p class="mb-1"><strong>On Hand:</strong> {{ number_format($stockSummary['on_hand']) }}</p>
resources/views/inventories/index.blade.php:56:                    <p class="mb-1"><strong>Reserved:</strong> {{ number_format($stockSummary['reserved']) }}</p>
resources/views/inventories/index.blade.php:57:                    <p class="mb-0"><strong>Available:</strong> {{ number_format($stockSummary['available']) }}</p>
resources/views/inventories/index.blade.php:63:                <div class="card-header">Thong ke theo ngay: {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</div>
resources/views/inventories/index.blade.php:65:                    <p class="mb-1"><strong>So phieu:</strong> {{ number_format($dailyStats['document_count']) }}</p>
resources/views/inventories/index.blade.php:66:                    <p class="mb-1"><strong>Nhap:</strong> {{ number_format($dailyStats['import_qty']) }}</p>
resources/views/inventories/index.blade.php:67:                    <p class="mb-1"><strong>Xuat:</strong> {{ number_format($dailyStats['export_qty']) }}</p>
resources/views/inventories/index.blade.php:68:                    <p class="mb-1"><strong>Dieu chinh:</strong> {{ number_format($dailyStats['adjustment_qty']) }}</p>
resources/views/inventories/index.blade.php:69:                    <p class="mb-0"><strong>Net:</strong> {{ number_format($dailyStats['net_qty']) }}</p>
resources/views/inventories/index.blade.php:75:                <div class="card-header">Thong ke tu {{ \Carbon\Carbon::parse($rangeStats['from_date'])->format('d/m/Y') }} den {{ \Carbon\Carbon::parse($rangeStats['to_date'])->format('d/m/Y') }}</div>
resources/views/inventories/index.blade.php:77:                    <p class="mb-1"><strong>So phieu:</strong> {{ number_format($rangeStats['document_count']) }}</p>
resources/views/inventories/index.blade.php:78:                    <p class="mb-1"><strong>Nhap:</strong> {{ number_format($rangeStats['import_qty']) }}</p>
resources/views/inventories/index.blade.php:79:                    <p class="mb-1"><strong>Xuat:</strong> {{ number_format($rangeStats['export_qty']) }}</p>
resources/views/inventories/index.blade.php:80:                    <p class="mb-1"><strong>Dieu chinh:</strong> {{ number_format($rangeStats['adjustment_qty']) }}</p>
resources/views/inventories/index.blade.php:81:                    <p class="mb-0"><strong>Net:</strong> {{ number_format($rangeStats['net_qty']) }}</p>
resources/views/inventories/index.blade.php:87:    <h5>Chi tiet ton kho</h5>
resources/views/inventories/index.blade.php:91:                <th>ID</th>
resources/views/inventories/index.blade.php:92:                <th>Product Variant</th>
resources/views/inventories/index.blade.php:93:                <th>Warehouse</th>
resources/views/inventories/index.blade.php:94:                <th>On Hand</th>
resources/views/inventories/index.blade.php:95:                <th>Reserved</th>
resources/views/inventories/index.blade.php:96:                <th>Available</th>
resources/views/inventories/index.blade.php:97:                <th>Low Stock Threshold</th>
resources/views/inventories/index.blade.php:98:                <th>Created At</th>
resources/views/inventories/index.blade.php:99:                <th>Updated At</th>
resources/views/inventories/index.blade.php:105:                <td>{{ $inventory->id }}</td>
resources/views/inventories/index.blade.php:109:                    <small class="text-muted">{{ $inventory->productVariant->product->name ?? '' }}</small>
resources/views/inventories/index.blade.php:111:                <td>{{ $inventory->warehouse->name ?? ('#' . $inventory->warehouse_id) }}</td>
resources/views/inventories/index.blade.php:112:                <td>{{ $inventory->on_hand }}</td>
resources/views/inventories/index.blade.php:113:                <td>{{ $inventory->reserved }}</td>
resources/views/inventories/index.blade.php:114:                <td>{{ $inventory->available }}</td>
resources/views/inventories/index.blade.php:115:                <td>{{ $inventory->low_stock_threshold }}</td>
resources/views/inventories/index.blade.php:116:                <td>{{ $inventory->created_at }}</td>
resources/views/inventories/index.blade.php:117:                <td>{{ $inventory->updated_at }}</td>
resources/views/inventories/create.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Add Inventory Record</h1>
resources/views/inventories/create.blade.php:13:            <h6 class="m-0 font-weight-bold text-primary">New Inventory Details</h6>
resources/views/order-returns/index.blade.php:6:        <h1 class="mb-0">Don tra hang</h1>
resources/views/order-returns/index.blade.php:8:            <a href="{{ route('order-returns.create') }}" class="btn btn-primary">Tao don tra hang</a>
resources/views/order-returns/index.blade.php:22:                <th>ID</th>
resources/views/order-returns/index.blade.php:23:                <th>Ma don</th>
resources/views/order-returns/index.blade.php:24:                <th>Customer</th>
resources/views/order-returns/index.blade.php:25:                <th>Kho nhap</th>
resources/views/order-returns/index.blade.php:26:                <th>Status</th>
resources/views/order-returns/index.blade.php:27:                <th>Phan loai</th>
resources/views/order-returns/index.blade.php:28:                <th>Tien refund</th>
resources/views/order-returns/index.blade.php:29:                <th>Reason</th>
resources/views/order-returns/index.blade.php:30:                <th>Nguoi tao</th>
resources/views/order-returns/index.blade.php:31:                <th>Created At</th>
resources/views/order-returns/index.blade.php:32:                <th>Thao tac</th>
resources/views/order-returns/index.blade.php:38:                <td>{{ $return->id }}</td>
resources/views/order-returns/index.blade.php:39:                <td>{{ $return->order->code ?? 'N/A' }}</td>
resources/views/order-returns/index.blade.php:40:                <td>{{ $return->customer->name ?? 'N/A' }}</td>
resources/views/order-returns/index.blade.php:41:                <td>{{ $return->warehouse->name ?? 'N/A' }}</td>
resources/views/order-returns/index.blade.php:55:                        <span class="badge bg-danger">Tra toan bo</span>
resources/views/order-returns/index.blade.php:57:                        <span class="badge bg-warning text-dark">Tra mot phan</span>
resources/views/order-returns/index.blade.php:65:                        <div class="small text-muted">TX#{{ $return->refundTransaction->id }}</div>
resources/views/order-returns/index.blade.php:68:                <td>{{ $return->reason }}</td>
resources/views/order-returns/index.blade.php:69:                <td>{{ $return->creator->name ?? 'N/A' }}</td>
resources/views/order-returns/index.blade.php:70:                <td>{{ $return->created_at?->format('d/m/Y H:i') }}</td>
resources/views/order-returns/index.blade.php:72:                    <a href="{{ route('order-returns.show', $return) }}" class="btn btn-sm btn-outline-secondary">Chi tiet</a>
resources/views/order-returns/index.blade.php:77:                            <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('Xac nhan ship da nhan hang tra?')">Ship xac nhan</button>
resources/views/order-returns/index.blade.php:84:                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Xac nhan kho da nhap hang tra?')">Kho xac nhan nhap</button>
resources/views/order-returns/create.blade.php:5:    <h1 class="mb-3">Tao don tra hang</h1>
resources/views/order-returns/create.blade.php:22:                <label class="form-label">Don hang</label>
resources/views/order-returns/create.blade.php:24:                    <option value="">Chon don hang</option>
resources/views/order-returns/create.blade.php:33:                <label class="form-label">Kho nhap hang tra ve</label>
resources/views/order-returns/create.blade.php:35:                    <option value="">Chon kho</option>
resources/views/order-returns/create.blade.php:37:                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
resources/views/order-returns/create.blade.php:42:                <label class="form-label">Ly do tra hang</label>
resources/views/order-returns/create.blade.php:46:                <label class="form-label">Hinh anh xac nhan tra hang</label>
resources/views/order-returns/create.blade.php:50:                <label class="form-label">Ghi chu</label>
resources/views/order-returns/create.blade.php:55:        <h5>Danh sach san pham tra</h5>
resources/views/order-returns/create.blade.php:61:            <button type="submit" class="btn btn-primary">Luu don tra hang</button>
resources/views/order-returns/create.blade.php:62:            <a href="{{ route('order-returns.index') }}" class="btn btn-secondary">Quay lai</a>
resources/views/order-returns/create.blade.php:86:                        <label class="form-label">San pham</label>
resources/views/order-returns/create.blade.php:91:                        <label class="form-label">So luong</label>
resources/views/order-returns/create.blade.php:95:                        <label class="form-label">Tinh trang</label>
resources/views/order-returns/create.blade.php:96:                        <input type="text" name="items[${idx}][condition]" class="form-control" placeholder="Vi du: moi, tray xuoc nhe...">
resources/views/order-returns/show.blade.php:6:        <h1 class="mb-0">Chi tiet don tra hang #{{ $return->id }}</h1>
resources/views/order-returns/show.blade.php:7:        <a href="{{ route('order-returns.index') }}" class="btn btn-secondary">Quay lai</a>
resources/views/order-returns/show.blade.php:20:                <div class="col-md-3"><strong>Don hang:</strong> {{ $return->order->code ?? 'N/A' }}</div>
resources/views/order-returns/show.blade.php:21:                <div class="col-md-3"><strong>Khach hang:</strong> {{ $return->customer->name ?? 'N/A' }}</div>
resources/views/order-returns/show.blade.php:22:                <div class="col-md-3"><strong>Kho nhap:</strong> {{ $return->warehouse->name ?? 'N/A' }}</div>
resources/views/order-returns/show.blade.php:23:                <div class="col-md-3"><strong>Trang thai:</strong> {{ $return->status }}</div>
resources/views/order-returns/show.blade.php:24:                <div class="col-md-3"><strong>Loai tra:</strong> {{ $return->return_scope === 'full' ? 'Tra toan bo' : ($return->return_scope === 'partial' ? 'Tra mot phan' : '-') }}</div>
resources/views/order-returns/show.blade.php:25:                <div class="col-md-3"><strong>Tien refund:</strong> {{ number_format((float) ($return->refund_amount ?? 0), 0, ',', '.') }}</div>
resources/views/order-returns/show.blade.php:26:                <div class="col-md-6"><strong>Ly do:</strong> {{ $return->reason ?: '-' }}</div>
resources/views/order-returns/show.blade.php:27:                <div class="col-md-6"><strong>Ghi chu:</strong> {{ $return->note ?: '-' }}</div>
resources/views/order-returns/show.blade.php:28:                <div class="col-md-4"><strong>Nguoi tao:</strong> {{ $return->creator->name ?? 'N/A' }}</div>
resources/views/order-returns/show.blade.php:29:                <div class="col-md-4"><strong>Ship xac nhan:</strong> {{ $return->shipConfirmer->name ?? '-' }} {{ $return->ship_confirmed_at ? '(' . $return->ship_confirmed_at->format('d/m/Y H:i') . ')' : '' }}</div>
resources/views/order-returns/show.blade.php:30:                <div class="col-md-4"><strong>Kho xac nhan:</strong> {{ $return->warehouseConfirmer->name ?? '-' }} {{ $return->warehouse_confirmed_at ? '(' . $return->warehouse_confirmed_at->format('d/m/Y H:i') . ')' : '' }}</div>
resources/views/order-returns/show.blade.php:31:                <div class="col-md-4"><strong>Refund TX:</strong> {{ $return->refundTransaction ? ('#' . $return->refundTransaction->id) : '-' }}</div>
resources/views/order-returns/show.blade.php:37:        <div class="card-header">San pham tra hang</div>
resources/views/order-returns/show.blade.php:43:                        <th>San pham</th>
resources/views/order-returns/show.blade.php:44:                        <th>Variant</th>
resources/views/order-returns/show.blade.php:45:                        <th>So luong</th>
resources/views/order-returns/show.blade.php:46:                        <th>Tinh trang</th>
resources/views/order-returns/show.blade.php:52:                            <td>{{ $loop->iteration }}</td>
resources/views/order-returns/show.blade.php:53:                            <td>{{ $item->productVariant->product->name ?? 'N/A' }}</td>
resources/views/order-returns/show.blade.php:54:                            <td>{{ $item->productVariant->name ?? 'N/A' }}</td>
resources/views/order-returns/show.blade.php:55:                            <td>{{ $item->quantity }}</td>
resources/views/order-returns/show.blade.php:56:                            <td>{{ $item->condition ?: '-' }}</td>
resources/views/order-returns/show.blade.php:59:                        <tr><td colspan="5" class="text-center text-muted">Khong co san pham</td></tr>
resources/views/order-returns/show.blade.php:70:                <button type="submit" class="btn btn-info" onclick="return confirm('Xac nhan ship da nhan hang tra?')">Ship xac nhan</button>
resources/views/order-returns/show.blade.php:77:                <button type="submit" class="btn btn-success" onclick="return confirm('Xac nhan kho da nhap hang tra?')">Kho xac nhan nhap</button>
resources/views/profile/edit.blade.php:5:    <h2>Chỉnh sửa hồ sơ</h2>
resources/views/profile/edit.blade.php:20:                            <label for="name" class="form-label">Tên</label>
resources/views/profile/edit.blade.php:26:                            <label for="email" class="form-label">Email</label>
resources/views/profile/edit.blade.php:32:                            <label for="password" class="form-label">Mật khẩu mới</label>
resources/views/profile/edit.blade.php:38:                            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
resources/views/profile/edit.blade.php:63:            <button type="submit" class="btn btn-primary">Cập nhật</button>
resources/views/roles/edit.blade.php:5:    <h2>Cập nhật Vai trò</h2>
resources/views/roles/edit.blade.php:12:            <label for="name" class="form-label">Tên Role</label>
resources/views/roles/edit.blade.php:18:                <label class="form-label mb-0">Phân quyền chi tiết (Permissions)</label>
resources/views/roles/edit.blade.php:20:                    <button type="button" class="btn btn-sm btn-outline-primary" id="checkAllPermissions">Check All</button>
resources/views/roles/edit.blade.php:21:                    <button type="button" class="btn btn-sm btn-outline-secondary" id="resetPermissions">Reset</button>
resources/views/roles/edit.blade.php:54:        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
resources/views/roles/edit.blade.php:55:        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/roles/index.blade.php:5:    <h2>Danh sách Role</h2>
resources/views/roles/index.blade.php:10:                <th>ID</th>
resources/views/roles/index.blade.php:11:                <th>Tên role</th>
resources/views/roles/index.blade.php:12:                <th>Mô tả</th>
resources/views/roles/index.blade.php:13:                <th>Quyền</th>
resources/views/roles/index.blade.php:14:                <th>Thao tác</th>
resources/views/roles/index.blade.php:20:                <td>{{ $role->id }}</td>
resources/views/roles/index.blade.php:21:                <td>{{ $role->name }}</td>
resources/views/roles/index.blade.php:22:                <td>{{ $role->description }}</td>
resources/views/roles/index.blade.php:25:                        <span class="badge bg-info">{{ $perm->name }}</span>
resources/views/roles/index.blade.php:29:                    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-warning">Sửa</a>
resources/views/roles/index.blade.php:32:                        <button onclick="return confirm('Xóa role này?')" class="btn btn-sm btn-danger">Xóa</button>
resources/views/roles/create.blade.php:5:    <h2>Thêm Role mới</h2>
resources/views/roles/create.blade.php:9:            <label for="name">Tên Role</label>
resources/views/roles/create.blade.php:13:            <label for="description">Mô tả</label>
resources/views/roles/create.blade.php:16:        <button type="submit" class="btn btn-success">Thêm</button>
resources/views/roles/show.blade.php:5:    <h2>Chi tiết Role</h2>
resources/views/roles/show.blade.php:7:    <p><strong>ID:</strong> {{ $role->id }}</p>
resources/views/roles/show.blade.php:8:    <p><strong>Tên Role:</strong> {{ $role->name }}</p>
resources/views/roles/show.blade.php:9:    <p><strong>Mô tả:</strong> {{ $role->description }}</p>
resources/views/roles/show.blade.php:10:    <p><strong>Ngày tạo:</strong> {{ $role->created_at->format('d/m/Y') }}</p>
resources/views/roles/show.blade.php:12:    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning">Sửa</a>
resources/views/roles/show.blade.php:13:    <a href="{{ route('roles.index') }}" class="btn btn-secondary">Quay lại</a>
resources/views/roles/show.blade.php:18:        <button class="btn btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa role này không?')">Xóa</button>
resources/views/transactions/index.blade.php:4:    <h2>Danh sách giao dịch</h2>
resources/views/transactions/index.blade.php:12:                <th>ID</th>
resources/views/transactions/index.blade.php:14:                <th>Khách hàng</th>
resources/views/transactions/index.blade.php:15:                <th>Số tiền</th>
resources/views/transactions/index.blade.php:16:                <th>Loại</th>
resources/views/transactions/index.blade.php:17:                <th>Phương thức</th>
resources/views/transactions/index.blade.php:18:                <th>Ghi chú</th>
resources/views/transactions/index.blade.php:19:                <th>Thời gian</th>
resources/views/transactions/index.blade.php:25:                    <td>{{ $t->id }}</td>
resources/views/transactions/index.blade.php:26:                    <td>@if($t->order_id)<a href="{{ route('orders.show', $t->order_id) }}">#{{ $t->order_id }}</a>@endif</td>
resources/views/transactions/index.blade.php:27:                    <td>@if($t->customer_id){{ $t->customer->name }}@endif</td>
resources/views/transactions/index.blade.php:28:                    <td>{{ number_format($t->amount,0,',','.') }}</td>
resources/views/transactions/index.blade.php:29:                    <td>{{ $t->type }}</td>
resources/views/transactions/index.blade.php:30:                    <td>{{ $t->method }}</td>
resources/views/transactions/index.blade.php:31:                    <td>{{ $t->note }}</td>
resources/views/transactions/index.blade.php:32:                    <td>{{ $t->created_at }}</td>
resources/views/transactions/create.blade.php:4:    <h2>Thêm giao dịch</h2>
resources/views/transactions/create.blade.php:20:                    <div><b>Tổng tiền:</b> {{ number_format($order->total, 0, ',', '.') }} đ</div>
resources/views/transactions/create.blade.php:22:                    <div><b>Còn phải thanh toán:</b> <span class="text-danger fw-bold">{{ number_format($remain, 0, ',', '.') }} đ</span></div>
resources/views/transactions/create.blade.php:32:                        <option value="{{ $order->id }}">#{{ $order->code }} - {{ $order->customer->name ?? '' }}</option>
resources/views/transactions/create.blade.php:38:            <label>Tổng tiền đơn hàng: <span id="order_total_text" class="fw-bold text-danger"></span></label>
resources/views/transactions/create.blade.php:41:                <label class="form-check-label" for="pay_full_order">Thanh toán toàn bộ</label>
resources/views/transactions/create.blade.php:46:            <label>Khách hàng (nếu có)</label>
resources/views/transactions/create.blade.php:48:                <input type="text" id="customer_name" class="form-control" placeholder="Chọn khách hàng" readonly>
resources/views/transactions/create.blade.php:50:                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customerModal">Chọn khách hàng</button>
resources/views/transactions/create.blade.php:55:            <label>Số tiền thanh toán</label>
resources/views/transactions/create.blade.php:60:                    <input type="checkbox" id="pay_full_order"> <label for="pay_full_order" class="ms-1 mb-0">Thanh toán đủ</label>
resources/views/transactions/create.blade.php:66:            <label>Loại giao dịch</label>
resources/views/transactions/create.blade.php:68:                <option value="payment">Thanh toán</option>
resources/views/transactions/create.blade.php:69:                <option value="refund">Hoàn trả</option>
resources/views/transactions/create.blade.php:70:                <option value="fee">Chi phí</option>
resources/views/transactions/create.blade.php:71:                <option value="extra_income">Thu khác</option>
resources/views/transactions/create.blade.php:72:                <option value="extra_expense">Chi khác</option>
resources/views/transactions/create.blade.php:76:            <label>Phương thức</label>
resources/views/transactions/create.blade.php:80:            <label>Ghi chú</label>
resources/views/transactions/create.blade.php:83:        <button class="btn btn-primary">Lưu giao dịch</button>
resources/views/welcome.blade.php:219:                                        <div class="hero-eyebrow">{{ $settings['slogan']->value ?? 'Giải pháp thực phẩm chuyên nghiệp' }}</div>
resources/views/welcome.blade.php:220:                                        <h1 class="hero-title">{{ $settings['brand_name']->value ?? 'Hoàng Long TNT' }}</h1>
resources/views/welcome.blade.php:221:                                        <p class="hero-desc">Giao hàng tận nơi.</p>
resources/views/welcome.blade.php:223:                                            <a href="{{ route('pages.product_list') }}" class="btn btn-warning me-2">Xem sản phẩm</a>
resources/views/welcome.blade.php:224:                                            <a href="{{ route('pages.contact') }}" class="btn btn-outline-light">Liên hệ ngay</a>
resources/views/welcome.blade.php:240:                                        <div class="hero-eyebrow">{{ $settings['slogan']->value ?? 'Giải pháp thực phẩm chuyên nghiệp' }}</div>
resources/views/welcome.blade.php:241:                                        <h1 class="hero-title">{{ $settings['brand_name']->value ?? 'Hoàng Long TNT' }}</h1>
resources/views/welcome.blade.php:242:                                        <p class="hero-desc">Chua có hình ảnh</p>
resources/views/welcome.blade.php:244:                                            <a href="{{ route('pages.product_list') }}" class="btn btn-warning me-2">Xem sản phẩm</a>
resources/views/welcome.blade.php:245:                                            <a href="{{ route('pages.contact') }}" class="btn btn-outline-light">Liên hệ ngay</a>
resources/views/welcome.blade.php:263:                <h4 class="brand-color text-uppercase fw-bold  text-center">Sản phẩm chủ đạo</h4>
resources/views/welcome.blade.php:264:                <!--a href="{{ route('pages.product_list') }}" class="btn btn-outline-dark btn-sm">Xem tất cả</a-->
resources/views/welcome.blade.php:285:                                <h5><a href="{{ route('pages.variant_detail', $variant) }}" class="text-uppercase">{{ $variant->product->name }} - {{ $variant->name }}</a></h5>
resources/views/welcome.blade.php:287:                                    <p class="product-meta">Mã sản phẩm: {{ $variant->sku }}</p>
resources/views/welcome.blade.php:289:                                <p class="product-price">{{ number_format($variant->final_price, 0, '.', ',') }} VNĐ</p>
resources/views/welcome.blade.php:291:                                    <a href="{{ route('pages.variant_detail', $variant) }}" class="btn  btn-brand btn-sm">Chi tiết</a>
resources/views/welcome.blade.php:315:                            <span>Về chúng tôi</span>
resources/views/welcome.blade.php:316:                            <h2>SỨ MỆNH SẢN PHẨM TƯƠI SẠCH </h2>
resources/views/welcome.blade.php:319:                           <p>Hoàng long TNT mang lại giải pháp thực phẩm tươi sạch cho hộ gia đình và doanh nghiệp giúp cuộc sống thêm an toàn </p>
resources/views/welcome.blade.php:324:                                            <input type="text" placeholder="Name" name="fname">
resources/views/welcome.blade.php:327:                                            <input type="text" placeholder="Email" name="email">
resources/views/welcome.blade.php:330:                                    <input type="text" placeholder="Subject" name="subject">
resources/views/welcome.blade.php:331:                                    <textarea placeholder="Your Question" name="question"></textarea>
resources/views/welcome.blade.php:332:                                    <button type="submit" class="site-btn">GỬI LIÊN HỆ</button>
resources/views/welcome.blade.php:333:                                    <button type="reset" class="site-btn partner-btn">NHẬP LẠI</button>
resources/views/welcome.blade.php:349:                                <h6>Gà</h6>
resources/views/welcome.blade.php:357:                                <h6>Vịt</h6>
resources/views/welcome.blade.php:365:                                <h6>Bò</h6>
resources/views/welcome.blade.php:373:                                <h6>Heo</h6>
resources/views/welcome.blade.php:381:                                <h6>Rau xanh</h6>
resources/views/welcome.blade.php:389:                                <h6>Trái cây</h6>
resources/views/welcome.blade.php:403:                        <span>Bản tin hàng ngày</span>
resources/views/welcome.blade.php:404:                        <h2>TIN MỚI CẬP NHẬT</h2> 
resources/views/welcome.blade.php:426:                                <h5>{{ $post->title }}</h5>
resources/views/welcome.blade.php:427:                                <p>{{ $post->excerpt }}.</p>
resources/views/welcome.blade.php:428:                                <a href="{{ route('posts.show', $post) }}">Xem thêm <i class="fa fa-long-arrow-right"></i></a>
resources/views/welcome.blade.php:450:                            aria-label="One"></iframe>
resources/views/inventory-movements/edit.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Edit Inventory Movement</h1>
resources/views/inventory-movements/edit.blade.php:13:            <h6 class="m-0 font-weight-bold text-primary">Edit Inventory Movement Details</h6>
resources/views/inventory-movements/_form.blade.php:2:    <label for="inventory_id" class="form-label">Inventory</label>
resources/views/inventory-movements/_form.blade.php:4:        <option value="">Select an Inventory</option>
resources/views/inventory-movements/_form.blade.php:17:    <label for="quantity" class="form-label">Quantity</label>
resources/views/inventory-movements/_form.blade.php:25:    <label for="type" class="form-label">Type</label>
resources/views/inventory-movements/_form.blade.php:27:        <option value="">Select a Type</option>
resources/views/inventory-movements/_form.blade.php:28:        <option value="purchase" {{ (old('type', $inventoryMovement->type ?? '') == 'purchase') ? 'selected' : '' }}>Purchase</option>
resources/views/inventory-movements/_form.blade.php:29:        <option value="sale" {{ (old('type', $inventoryMovement->type ?? '') == 'sale') ? 'selected' : '' }}>Sale</option>
resources/views/inventory-movements/_form.blade.php:30:        <option value="adjustment" {{ (old('type', $inventoryMovement->type ?? '') == 'adjustment') ? 'selected' : '' }}>Adjustment</option>
resources/views/inventory-movements/_form.blade.php:31:        <option value="transfer" {{ (old('type', $inventoryMovement->type ?? '') == 'transfer') ? 'selected' : '' }}>Transfer</option>
resources/views/inventory-movements/_form.blade.php:39:    <label for="reference_id" class="form-label">Reference ID</label>
resources/views/inventory-movements/_form.blade.php:47:    <label for="reference_type" class="form-label">Reference Type</label>
resources/views/inventory-movements/_form.blade.php:54:<button type="submit" class="btn btn-primary">Submit</button>
resources/views/inventory-movements/_form.blade.php:55:<a href="{{ route('inventory-movements.index') }}" class="btn btn-secondary">Cancel</a>
resources/views/inventory-movements/index.blade.php:5:    <h1>Inventory Movements</h1>
resources/views/inventory-movements/index.blade.php:9:                <th>ID</th>
resources/views/inventory-movements/index.blade.php:10:                <th>Inventory ID</th>
resources/views/inventory-movements/index.blade.php:11:                <th>Quantity</th>
resources/views/inventory-movements/index.blade.php:12:                <th>Type</th>
resources/views/inventory-movements/index.blade.php:13:                <th>Reference</th>
resources/views/inventory-movements/index.blade.php:14:                <th>User</th>
resources/views/inventory-movements/index.blade.php:15:                <th>Created At</th>
resources/views/inventory-movements/index.blade.php:21:                <td>{{ $movement->id }}</td>
resources/views/inventory-movements/index.blade.php:22:                <td>{{ $movement->inventory_id }}</td>
resources/views/inventory-movements/index.blade.php:23:                <td>{{ $movement->quantity }}</td>
resources/views/inventory-movements/index.blade.php:24:                <td>{{ $movement->type }}</td>
resources/views/inventory-movements/index.blade.php:25:                <td>{{ $movement->reference_type }} - {{ $movement->reference_id }}</td>
resources/views/inventory-movements/index.blade.php:26:                <td>{{ $movement->user->name ?? 'N/A' }}</td>
resources/views/inventory-movements/index.blade.php:27:                <td>{{ $movement->created_at }}</td>
resources/views/inventory-movements/create.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Add Inventory Movement</h1>
resources/views/inventory-movements/create.blade.php:13:            <h6 class="m-0 font-weight-bold text-primary">New Inventory Movement Details</h6>
resources/views/warehouses/edit.blade.php:5:    <h1>Edit Warehouse</h1>
resources/views/warehouses/edit.blade.php:10:        <button type="submit" class="btn btn-primary">Update</button>
resources/views/warehouses/_form.blade.php:2:    <label for="name" class="form-label">Name</label>
resources/views/warehouses/_form.blade.php:6:    <label for="address" class="form-label">Address</label>
resources/views/warehouses/_form.blade.php:7:    <textarea class="form-control" id="address" name="address">{{ old('address', $warehouse->address ?? '') }}</textarea>
resources/views/warehouses/_form.blade.php:10:    <label for="phone" class="form-label">Phone</label>
resources/views/warehouses/_form.blade.php:14:    <label for="status" class="form-label">Status</label>
resources/views/warehouses/_form.blade.php:16:        <option value="1" {{ old('status', $warehouse->status ?? '') == 1 ? 'selected' : '' }}>Active</option>
resources/views/warehouses/_form.blade.php:17:        <option value="0" {{ old('status', $warehouse->status ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
resources/views/warehouses/index.blade.php:5:    <h1>Warehouses</h1>
resources/views/warehouses/index.blade.php:7:        <a href="{{ route('warehouses.create') }}" class="btn btn-primary">Create Warehouse</a>
resources/views/warehouses/index.blade.php:12:                <th>ID</th>
resources/views/warehouses/index.blade.php:13:                <th>Name</th>
resources/views/warehouses/index.blade.php:14:                <th>Address</th>
resources/views/warehouses/index.blade.php:15:                <th>Phone</th>
resources/views/warehouses/index.blade.php:16:                <th>Status</th>
resources/views/warehouses/index.blade.php:17:                <th>Created At</th>
resources/views/warehouses/index.blade.php:18:                <th>Actions</th>
resources/views/warehouses/index.blade.php:24:                <td>{{ $warehouse->id }}</td>
resources/views/warehouses/index.blade.php:25:                <td>{{ $warehouse->name }}</td>
resources/views/warehouses/index.blade.php:26:                <td>{{ $warehouse->address }}</td>
resources/views/warehouses/index.blade.php:27:                <td>{{ $warehouse->phone }}</td>
resources/views/warehouses/index.blade.php:30:                        <span class="badge bg-success">Active</span>
resources/views/warehouses/index.blade.php:32:                        <span class="badge bg-danger">Inactive</span>
resources/views/warehouses/index.blade.php:35:                <td>{{ $warehouse->created_at }}</td>
resources/views/warehouses/index.blade.php:37:                    <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-warning">Edit</a>
resources/views/warehouses/index.blade.php:41:                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
resources/views/warehouses/create.blade.php:5:    <h1>Create Warehouse</h1>
resources/views/warehouses/create.blade.php:9:        <button type="submit" class="btn btn-primary">Create</button>
resources/views/inventory-adjustments/index.blade.php:5:        <h1>Inventory Adjustments</h1>
resources/views/components/breadcrumb.blade.php:19:                        <a href="{{ url('/') }}"><i class="fa fa-home"></i> Trang chủ</a>
resources/views/inventory-documents/edit.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Edit Inventory Document</h1>
resources/views/inventory-documents/edit.blade.php:13:            <h6 class="m-0 font-weight-bold text-primary">Edit Inventory Document</h6>
resources/views/inventory-documents/_form.blade.php:4:            <label for="document_date" class="form-label">Date</label>
resources/views/inventory-documents/_form.blade.php:13:            <label for="type" class="form-label">Type</label>
resources/views/inventory-documents/_form.blade.php:15:                <option value="import" {{ (old('type', $inventoryDocument->type ?? '') == 'import') ? 'selected' : '' }}>Import</option>
resources/views/inventory-documents/_form.blade.php:16:                <option value="export" {{ (old('type', $inventoryDocument->type ?? '') == 'export') ? 'selected' : '' }}>Export</option>
resources/views/inventory-documents/_form.blade.php:17:                <option value="adjustment" {{ (old('type', $inventoryDocument->type ?? '') == 'adjustment') ? 'selected' : '' }}>Adjustment</option>
resources/views/inventory-documents/_form.blade.php:29:            <label for="warehouse_id" class="form-label">Warehouse</label>
resources/views/inventory-documents/_form.blade.php:42:                        <option value="{{ $warehouse->id }}" {{ ((string) $selectedWarehouseId === (string) $warehouse->id) ? 'selected' : '' }}>{{ $warehouse->name }}</option>
resources/views/inventory-documents/_form.blade.php:53:            <label for="shipping_fee" class="form-label">Shipping Fee</label>
resources/views/inventory-documents/_form.blade.php:63:    <label for="notes" class="form-label">Notes</label>
resources/views/inventory-documents/_form.blade.php:64:    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $inventoryDocument->notes ?? '') }}</textarea>
resources/views/inventory-documents/_form.blade.php:72:<h4>Items</h4>
resources/views/inventory-documents/_form.blade.php:77:            <th>Product Variant</th>
resources/views/inventory-documents/_form.blade.php:78:            <th>Quantity</th>
resources/views/inventory-documents/_form.blade.php:79:            <th>Unit Cost</th>
resources/views/inventory-documents/_form.blade.php:96:<button type="button" id="add-item-btn" class="btn btn-success btn-sm">Add Item</button>
resources/views/inventory-documents/_form.blade.php:100:<button type="submit" class="btn btn-primary">Save Document</button>
resources/views/inventory-documents/_form.blade.php:101:<a href="{{ route('inventory-documents.index') }}" class="btn btn-secondary">Cancel</a>
resources/views/inventory-documents/index.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Inventory Documents</h1>
resources/views/inventory-documents/index.blade.php:9:        <a href="{{ route('inventory-documents.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add Inventory Document</a>
resources/views/inventory-documents/index.blade.php:20:            <h6 class="m-0 font-weight-bold text-primary">Inventory Document List</h6>
resources/views/inventory-documents/index.blade.php:27:                            <th>ID</th>
resources/views/inventory-documents/index.blade.php:28:                            <th>Type</th>
resources/views/inventory-documents/index.blade.php:29:                            <th>Warehouse</th>
resources/views/inventory-documents/index.blade.php:30:                            <th>Date</th>
resources/views/inventory-documents/index.blade.php:31:                            <th>User</th>
resources/views/inventory-documents/index.blade.php:32:                            <th>Actions</th>
resources/views/inventory-documents/index.blade.php:38:                                <td>{{ $document->id }}</td>
resources/views/inventory-documents/index.blade.php:39:                                <td>{{ $document->type }}</td>
resources/views/inventory-documents/index.blade.php:40:                                <td>{{ $document->warehouse->name }}</td>
resources/views/inventory-documents/index.blade.php:41:                                <td>{{ $document->document_date }}</td>
resources/views/inventory-documents/index.blade.php:42:                                <td>{{ $document->user->name ?? 'N/A' }}</td>
resources/views/inventory-documents/index.blade.php:45:                                        <a href="{{ route('inventory-documents.show', $document->id) }}" class="btn btn-sm btn-info">View</a>
resources/views/inventory-documents/index.blade.php:46:                                        <a href="{{ route('inventory-documents.edit', $document->id) }}" class="btn btn-sm btn-warning">Edit</a>
resources/views/inventory-documents/index.blade.php:49:                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
resources/views/inventory-documents/index.blade.php:55:                                <td colspan="6" class="text-center">No inventory documents found.</td>
resources/views/inventory-documents/item-row.blade.php:18:        <button type="button" class="btn btn-danger btn-sm remove-item-btn">Remove</button>
resources/views/inventory-documents/create.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Create Inventory Document</h1>
resources/views/inventory-documents/create.blade.php:13:            <h6 class="m-0 font-weight-bold text-primary">New Inventory Document</h6>
resources/views/inventory-documents/show.blade.php:8:        <h1 class="h3 mb-0 text-gray-800">Inventory Document #{{ $inventoryDocument->id }}</h1>
resources/views/inventory-documents/show.blade.php:13:            <h6 class="m-0 font-weight-bold text-primary">Document Details</h6>
resources/views/inventory-documents/show.blade.php:18:                    <p><strong>Date:</strong> {{ $inventoryDocument->document_date }}</p>
resources/views/inventory-documents/show.blade.php:19:                    <p><strong>Type:</strong> {{ $inventoryDocument->type }}</p>
resources/views/inventory-documents/show.blade.php:20:                    <p><strong>Warehouse:</strong> {{ $inventoryDocument->warehouse->name }}</p>
resources/views/inventory-documents/show.blade.php:23:                    <p><strong>User:</strong> {{ $inventoryDocument->user->name ?? 'N/A' }}</p>
resources/views/inventory-documents/show.blade.php:24:                    <p><strong>Shipping Fee:</strong> {{ number_format($inventoryDocument->shipping_fee, 2) }}</p>
resources/views/inventory-documents/show.blade.php:25:                    <p><strong>Notes:</strong> {{ $inventoryDocument->notes }}</p>
resources/views/inventory-documents/show.blade.php:31:            <h4>Items</h4>
resources/views/inventory-documents/show.blade.php:35:                        <th>Product Variant</th>
resources/views/inventory-documents/show.blade.php:36:                        <th>Quantity</th>
resources/views/inventory-documents/show.blade.php:37:                        <th>Unit Cost</th>
resources/views/inventory-documents/show.blade.php:38:                        <th>Total Cost</th>
resources/views/inventory-documents/show.blade.php:44:                            <td>{{ $item->productVariant->product->name }} ({{ $item->productVariant->sku }})</td>
resources/views/inventory-documents/show.blade.php:45:                            <td>{{ $item->quantity }}</td>
resources/views/inventory-documents/show.blade.php:46:                            <td>{{ number_format($item->unit_cost, 2) }}</td>
resources/views/inventory-documents/show.blade.php:47:                            <td>{{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
resources/views/inventory-documents/show.blade.php:53:            <a href="{{ route('inventory-documents.index') }}" class="btn btn-secondary">Back to List</a>
resources/views/variants/price-history-table.blade.php:4:            <th>Giá</th>
resources/views/variants/price-history-table.blade.php:5:            <th>Lý do</th>
resources/views/variants/price-history-table.blade.php:6:            <th>Ngày áp dụng</th>
resources/views/variants/price-history-table.blade.php:7:            <th>Người tạo</th>
resources/views/variants/price-history-table.blade.php:13:            <td>{{ number_format($rule->price, 0, ',', '.') }} đ</td>
resources/views/variants/price-history-table.blade.php:14:            <td>{{ $rule->reason }}</td>
resources/views/variants/price-history-table.blade.php:15:            <td>{{ \Carbon\Carbon::parse($rule->start_date)->format('d/m/Y H:i') }}</td>
resources/views/variants/price-history-table.blade.php:16:            <td>{{ $rule->creator->name ?? '' }}</td>
resources/views/variants/edit-price.blade.php:5:    <h4>Điều chỉnh giá - {{ $variant->product->name }} ({{ $variant->size }}, {{ $variant->quality }})</h4>
resources/views/variants/edit-price.blade.php:12:            <label class="form-label">Giá hiện tại</label>
resources/views/variants/edit-price.blade.php:18:            <label class="form-label">Giá mới</label>
resources/views/variants/edit-price.blade.php:23:            <label class="form-label">Lý do điều chỉnh</label>
resources/views/variants/edit-price.blade.php:27:        <button type="submit" class="btn btn-primary">Lưu</button>
resources/views/variants/edit-price.blade.php:29:            <a href="{{ route('product-variants.index') }}" class="btn btn-secondary">Hủy</a>
resources/views/variants/edit-price.blade.php:31:            <a href="{{ route('products.edit', $variant->product_id) }}" class="btn btn-secondary">Hủy</a>
resources/views/variants/edit-price.blade.php:37:    <h5>Lịch sử điều chỉnh giá</h5>
resources/views/variants/edit-price.blade.php:41:                <th>Ngày</th>
resources/views/variants/edit-price.blade.php:42:                <th>Giá cũ</th>
resources/views/variants/edit-price.blade.php:43:                <th>Giá mới</th>
resources/views/variants/edit-price.blade.php:44:                <th>Người thay đổi</th>
resources/views/variants/edit-price.blade.php:45:                <th>Lý do</th>
resources/views/variants/edit-price.blade.php:51:                <td>{{ $rule->start_date }}</td>
resources/views/variants/edit-price.blade.php:58:                <td>{{ number_format($rule->price, 0, ',', '.') }}</td>
resources/views/variants/edit-price.blade.php:59:                <td>{{ $log && $log->user ? $log->user->name : 'System' }}</td>
resources/views/variants/edit-price.blade.php:60:                <td>{{ $rule->reason }}</td>

## Controller user-facing message candidates
app/Http/Controllers/HomeController.php:48:            ->with(['product.avatar.media', 'product.gallery.media', 'latestPriceRule', 'avatar.media'])
app/Http/Controllers/HomeController.php:73:        $variants = $query->with(['product.avatar.media', 'latestPriceRule', 'media'])->paginate(10);
app/Http/Controllers/Admin/PostCategoryController.php:40:            ->with('success', 'Post category created successfully.');
app/Http/Controllers/Admin/PostCategoryController.php:71:            ->with('success', 'Post category updated successfully.');
app/Http/Controllers/Admin/PostCategoryController.php:82:            ->with('success', 'Post category deleted successfully.');
app/Http/Controllers/Admin/SettingController.php:40:        return redirect()->back()->with('success', 'Settings updated successfully.');
app/Http/Controllers/CartController.php:19:            return redirect()->route('cart.show')->with('error', 'Giỏ hàng của bạn đang trống');
app/Http/Controllers/CartController.php:47:            'message' => 'Product added to cart successfully!',
app/Http/Controllers/CartController.php:75:            'message' => 'Product removed successfully!',
app/Http/Controllers/CartController.php:86:                'message' => 'Quantity must be at least 1.',
app/Http/Controllers/CartController.php:100:            'message' => 'Cart updated successfully!',
app/Http/Controllers/WarehouseController.php:42:            ->with('success', 'Warehouse created successfully.');
app/Http/Controllers/WarehouseController.php:76:            ->with('success', 'Warehouse updated successfully.');
app/Http/Controllers/WarehouseController.php:87:            ->with('success', 'Warehouse deleted successfully.');
app/Http/Controllers/AdminNotificationController.php:47:        return back()->with('success', 'Da danh dau tat ca thong bao la da doc.');
app/Http/Controllers/CustomerAddressController.php:111:            ->with('success', 'Địa chỉ đã được thêm thành công.');
app/Http/Controllers/CustomerAddressController.php:154:            ->with('success', 'Địa chỉ đã được cập nhật thành công.');
app/Http/Controllers/CustomerAddressController.php:164:            ->with('success', 'Địa chỉ đã được xóa thành công.');
app/Http/Controllers/ProductVariantPriceController.php:89:            return redirect()->route('product-variants.index')->with('success', 'Cập nhật giá thành công');
app/Http/Controllers/ProductVariantPriceController.php:92:            ->with('success', 'Cập nhật giá thành công');
app/Http/Controllers/RoleController.php:44:        return redirect()->route('roles.index')->with('success', 'Thêm role thành công!');
app/Http/Controllers/RoleController.php:87:        return redirect()->route('roles.index')->with('success', 'Cập nhật vai trò thành công!');
app/Http/Controllers/RoleController.php:98:        return redirect()->route('roles.index')->with('success', 'Xóa role thành công');
app/Http/Controllers/InventoryDocumentController.php:105:        return redirect()->route('inventory-documents.index')->with('success', 'Inventory document created successfully.');
app/Http/Controllers/InventoryDocumentController.php:216:        return redirect()->route('inventory-documents.index')->with('success', 'Inventory document updated successfully.');
app/Http/Controllers/InventoryDocumentController.php:248:        return redirect()->route('inventory-documents.index')->with('success', 'Inventory document deleted successfully.');
app/Http/Controllers/OrderController.php:145:            return redirect()->route('orders.index')->with('error', 'No variant ID provided.');
app/Http/Controllers/OrderController.php:152:            return redirect()->route('orders.index')->with('error', 'Variant not found.');
app/Http/Controllers/OrderController.php:241:            return back()->with('error', $e->getMessage())->withInput();
app/Http/Controllers/OrderController.php:244:        return redirect()->route('site.orders.show', $order)->with('success', 'Order created successfully.');
app/Http/Controllers/OrderController.php:275:            return back()->with('error', $e->getMessage())->withInput();
app/Http/Controllers/OrderController.php:278:        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
app/Http/Controllers/OrderController.php:284:            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để tạo đơn hàng.');
app/Http/Controllers/OrderController.php:294:            return redirect()->route('cart.show')->with('error', 'Giỏ hàng trống');
app/Http/Controllers/OrderController.php:298:            return redirect()->route('cart.show')->with('error', 'Tai khoan warehouse chua duoc assign kho.');
app/Http/Controllers/OrderController.php:358:            return redirect()->route('cart.show')->with('error', $e->getMessage());
app/Http/Controllers/OrderController.php:363:        return redirect()->route('site.orders.show', $order->id)->with('success', 'Đơn hàng đã tạo thành công');
app/Http/Controllers/OrderController.php:536:        return back()->with('success', 'Don hang da duoc xac nhan.');
app/Http/Controllers/OrderController.php:550:        return back()->with('success', 'Kho da xac nhan bat dau dong hang.');
app/Http/Controllers/OrderController.php:581:        return back()->with('success', 'Da hoan thien dong hang. Don san sang cho shipper lay.');
app/Http/Controllers/OrderController.php:603:        return back()->with('success', 'Shipper da lay hang va don chuyen sang dang giao hang.');
app/Http/Controllers/OrderController.php:620:        return back()->with('success', 'Don hang dang duoc giao.');
app/Http/Controllers/OrderController.php:646:        return back()->with('success', 'Da xac nhan giao hang thanh cong.');
app/Http/Controllers/OrderController.php:670:            return back()->with('error', 'Can thanh toan du de hoan tat don hang.');
app/Http/Controllers/OrderController.php:706:        return back()->with('success', 'Da ghi nhan thanh toan va hoan tat don hang.');
app/Http/Controllers/OrderController.php:716:            return back()->with('error', 'Chi duoc tao refund sau khi don da giao hoac dang giao.');
app/Http/Controllers/OrderController.php:733:        return back()->with('success', 'Don hang da hoan tat.');
app/Http/Controllers/OrderController.php:748:        return back()->with('success', 'Don hang da bi huy va da giai phong hang booking.');
app/Http/Controllers/OrderController.php:776:            default => back()->with('error', 'Khong the chuyen trang thai don hang.'),
app/Http/Controllers/OrderApprovalController.php:52:            return back()->with('error', 'Ban khong co quyen duyet don hang.');
app/Http/Controllers/OrderApprovalController.php:60:            return back()->with('success', 'Đã duyệt bước hiện tại thành công.');
app/Http/Controllers/OrderApprovalController.php:62:            return back()->with('error', $e->getMessage());
app/Http/Controllers/OrderApprovalController.php:74:            return back()->with('error', 'Ban khong co quyen tu choi don hang.');
app/Http/Controllers/OrderApprovalController.php:82:            return back()->with('success', 'Đơn hàng đã bị từ chối.');
app/Http/Controllers/OrderApprovalController.php:84:            return back()->with('error', $e->getMessage());
app/Http/Controllers/ProductVariantController.php:41:        return redirect()->route('product-variants.index')->with('success', 'Đã nhân bản biến thể thành công!');
app/Http/Controllers/ProductVariantController.php:95:        return redirect()->route('product-variants.index')->with('success', 'Đã cập nhật biến thể thành công!');
app/Http/Controllers/ProductVariantController.php:194:        return redirect()->route('product-variants.index')->with('success', 'Đã thêm biến thể thành công!');
app/Http/Controllers/CustomerTypeController.php:47:            ->with('success', 'Thêm loại khách hàng thành công!');
app/Http/Controllers/CustomerTypeController.php:73:            ->with('success', 'Cập nhật loại khách hàng thành công!');
app/Http/Controllers/CustomerTypeController.php:82:            ->with('success', 'Xóa loại khách hàng thành công!');
app/Http/Controllers/ProfileController.php:45:        return redirect()->route('profile.edit')->with('success', 'Hồ sơ đã được cập nhật.');
app/Http/Controllers/CustomerPopupController.php:55:                'message' => 'Khach hang da ton tai (ID: ' . $duplicateCustomer->id . ', Ten: ' . $duplicateCustomer->name . ').',
app/Http/Controllers/ApprovalWorkflowController.php:60:        return redirect()->route('approval-workflows.index')->with('success', 'Đã tạo quy trình xét duyệt thành công.');
app/Http/Controllers/ApprovalWorkflowController.php:108:        return redirect()->route('approval-workflows.index')->with('success', 'Đã cập nhật quy trình xét duyệt thành công.');
app/Http/Controllers/CustomerController.php:57:                return redirect()->route('customers.import.form')->with(['import_failures' => $errors]);
app/Http/Controllers/CustomerController.php:59:            return redirect()->route('customers.import.form')->with(['import_success' => 'Import khách hàng thành công!']);
app/Http/Controllers/CustomerController.php:61:            return redirect()->route('customers.import.form')->with(['import_errors' => [['row' => '-', 'attribute' => '-', 'errors' => [$e->getMessage()], 'values' => []]]]);
app/Http/Controllers/CustomerController.php:130:            ->with(['user', 'transactions'])
app/Http/Controllers/CustomerController.php:212:                ->with('error', 'Khach hang da ton tai (ID: ' . $duplicateCustomer->id . ', Ten: ' . $duplicateCustomer->name . '). Vui long kiem tra lai so dien thoai/email.');
app/Http/Controllers/CustomerController.php:224:        return redirect()->route('customers.index')->with('success', 'Thêm khách hàng thành công.');
app/Http/Controllers/CustomerController.php:269:        return redirect()->route('customers.index')->with('success', 'Cập nhật khách hàng thành công.');
app/Http/Controllers/CustomerController.php:276:        return redirect()->route('customers.index')->with('success', 'Xóa khách hàng thành công.');
app/Http/Controllers/CustomerController.php:290:        return redirect()->route('customers.index')->with('success', 'Đã xóa các khách hàng đã chọn.');
app/Http/Controllers/OrderReturnController.php:163:            return back()->with('error', 'Don hang chua co khach hang, khong the tao don tra hang.')->withInput();
app/Http/Controllers/OrderReturnController.php:187:                return back()->with('error', 'San pham tra hang khong ton tai trong don goc.')->withInput();
app/Http/Controllers/OrderReturnController.php:192:                return back()->with('error', 'So luong tra khong duoc vuot qua so luong da mua.')->withInput();
app/Http/Controllers/OrderReturnController.php:228:        return redirect()->route('order-returns.index')->with('success', 'Da tao don tra hang. Cho ship xac nhan.');
app/Http/Controllers/OrderReturnController.php:292:            return back()->with('error', 'Trang thai don tra hang khong hop le de ship xac nhan.');
app/Http/Controllers/OrderReturnController.php:305:        return back()->with('success', 'Ship da xac nhan don tra hang. Cho kho nhap hang.');
app/Http/Controllers/OrderReturnController.php:314:            return back()->with('error', 'Ban khong duoc phep xac nhan don tra hang nay.');
app/Http/Controllers/OrderReturnController.php:318:            return back()->with('error', 'Trang thai don tra hang khong hop le de kho xac nhan.');
app/Http/Controllers/OrderReturnController.php:426:        return back()->with('success', 'Kho da xac nhan va nhap hang tra ve kho thanh cong.');
app/Http/Controllers/MediaController.php:53:        return redirect()->back()->with('success', 'Tải ảnh thành công!');
app/Http/Controllers/MediaController.php:76:        return redirect()->route('media.library.popup')->with('success', 'Media updated successfully!');
app/Http/Controllers/MediaController.php:100:         return redirect()->route('media.gallery.popup')->with('success', 'Media updated successfully!');
app/Http/Controllers/MediaController.php:156:        return redirect()->route('media.index')->with('success', 'Media updated successfully!');
app/Http/Controllers/MediaController.php:204:        return redirect()->route('media.index')->with('success', 'Delete successfully!');
app/Http/Controllers/DashboardController.php:73:            ->with('product')
app/Http/Controllers/CategoryController.php:17:        $categories = Category::whereNull('parent_id')->with('children')->get(); 
app/Http/Controllers/CategoryController.php:44:        return redirect()->route('categories.index')->with('success', 'Tạo danh mục thành công!');
app/Http/Controllers/CategoryController.php:73:        return redirect()->route('categories.index')->with('success', 'Cập nhật danh mục thành công!');
app/Http/Controllers/CategoryController.php:88:        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
app/Http/Controllers/ProductController.php:111:        return redirect()->route('products.index')->with('success', 'Product created successfully!');
app/Http/Controllers/ProductController.php:181:                'message' => 'Cập nhật sản phẩm thành công!',
app/Http/Controllers/ProductController.php:335:            return redirect()->route('products.index')->with('success', 'Cập nhật sản phẩm thành công!');
app/Http/Controllers/ProductController.php:338:            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
app/Http/Controllers/ProductController.php:380:                    'message' => 'Sản phẩm đã được chuyển sang trạng thái đã xóa.',
app/Http/Controllers/ProductController.php:386:                ->with('success', 'Sản phẩm đã được chuyển sang trạng thái đã xóa.');
app/Http/Controllers/ProductController.php:391:                    'message' => 'Không thể cập nhật trạng thái sản phẩm. Lỗi: ' . $e->getMessage(),
app/Http/Controllers/ProductController.php:397:                ->with('error', 'Không thể cập nhật trạng thái sản phẩm!');
app/Http/Controllers/ProductController.php:419:                ->with('success', 'Sản phẩm đã được khôi phục thành công.');
app/Http/Controllers/ProductController.php:423:                ->with('error', 'Không thể khôi phục sản phẩm: ' . $e->getMessage());
app/Http/Controllers/PageController.php:47:            'message' => 'required',
app/Http/Controllers/PageController.php:52:        return redirect()->back()->with('success', 'Your message has been sent successfully!');
app/Http/Controllers/PageController.php:85:            ->with('success', 'Page created successfully.');
app/Http/Controllers/PageController.php:117:            ->with('success', 'Page updated successfully.');
app/Http/Controllers/PageController.php:128:            ->with('success', 'Page deleted successfully.');
app/Http/Controllers/PageController.php:166:        $variants = $query->with('product.avatar.media', 'product.gallery.media', 'latestPriceRule')->paginate(10);
app/Http/Controllers/PageController.php:182:        $products = $query->with('avatar.media')->paginate(10);
app/Http/Controllers/PageController.php:257:        return redirect()->route('pages.my_dashboard')->with('success', 'Profile updated successfully.');
app/Http/Controllers/PageController.php:274:                                          ->with('product', 'avatar.media', 'latestPriceRule')
app/Http/Controllers/PageController.php:291:            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để xem đơn hàng của mình.');
app/Http/Controllers/PageController.php:390:                ->with('error', 'Khach hang da ton tai (ID: ' . $duplicateCustomer->id . ', Ten: ' . $duplicateCustomer->name . '). Vui long kiem tra lai so dien thoai/email.');
app/Http/Controllers/PageController.php:395:        return back()->with('success', 'Customer created successfully.');
app/Http/Controllers/PageController.php:408:        return back()->with('success', 'Customer updated successfully.');
app/Http/Controllers/PageController.php:414:        return back()->with('success', 'Customer deleted successfully.');
app/Http/Controllers/PageController.php:422:            return back()->with('error', 'Không có khách hàng nào được chọn.');
app/Http/Controllers/PageController.php:427:        return back()->with('success', 'Đã xóa thành công các khách hàng đã chọn.');
app/Http/Controllers/PageController.php:451:            ->with('success', $message)
app/Http/Controllers/PageController.php:452:            ->with('importedCount', $importedCount)
app/Http/Controllers/PageController.php:453:            ->with('failedCount', $failedCount)
app/Http/Controllers/PageController.php:454:            ->with('failedRows', $failedRows);
app/Http/Controllers/PageController.php:488:        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
app/Http/Controllers/UserController.php:90:        return redirect()->route('users.bulk-assign-team.form')->with('success', $message);
app/Http/Controllers/UserController.php:129:        return redirect()->route('users.index')->with('success', 'Tạo user thành công');
app/Http/Controllers/UserController.php:161:        return redirect()->route('users.index')->with('success', 'Cập nhật user thành công');
app/Http/Controllers/UserController.php:168:        return redirect()->route('users.index')->with('success', 'Xóa user thành công');
app/Http/Controllers/TransactionController.php:64:        return redirect()->route('transactions.index')->with('success', 'Giao dịch đã được ghi nhận.');
app/Http/Controllers/AdminEventController.php:16:            ->with('actor')
app/Http/Controllers/BrandController.php:40:            ->with('success', 'Brand created successfully.');
app/Http/Controllers/BrandController.php:72:            ->with('success', 'Brand updated successfully.');
app/Http/Controllers/BrandController.php:80:            ->with('success', 'Brand deleted successfully.');
app/Http/Controllers/TeamController.php:31:        return redirect()->route('teams.index')->with('success', 'Tạo team thành công.');
app/Http/Controllers/TeamController.php:54:        return redirect()->route('teams.index')->with('success', 'Cập nhật team thành công.');
app/Http/Controllers/TeamController.php:60:            return back()->with('error', 'Không thể xóa team đang có user.');
app/Http/Controllers/TeamController.php:65:        return redirect()->route('teams.index')->with('success', 'Xóa team thành công.');
app/Http/Controllers/PermissionController.php:30:        return redirect()->route('permissions.index')->with('success', 'Thêm quyền thành công');
app/Http/Controllers/PermissionController.php:48:        return redirect()->route('permissions.index')->with('success', 'Cập nhật quyền thành công');
app/Http/Controllers/PermissionController.php:54:        return redirect()->route('permissions.index')->with('success', 'Xóa quyền thành công');
app/Http/Controllers/CompanyController.php:51:                return redirect()->route('companies.import.form')->with(['import_failures' => $errors]);
app/Http/Controllers/CompanyController.php:53:            return redirect()->route('companies.import.form')->with(['import_success' => 'Import công ty thành công!']);
app/Http/Controllers/CompanyController.php:55:            return redirect()->route('companies.import.form')->with(['import_errors' => [['row' => '-', 'attribute' => '-', 'errors' => [$e->getMessage()], 'values' => []]]]);
app/Http/Controllers/CompanyController.php:111:        return redirect()->route('companies.index')->with('success', 'Thêm công ty thành công.');
app/Http/Controllers/CompanyController.php:133:        return redirect()->route('companies.index')->with('success', 'Cập nhật công ty thành công.');
app/Http/Controllers/CompanyController.php:140:        return redirect()->route('companies.index')->with('success', 'Xóa công ty thành công.');
app/Http/Controllers/PostController.php:37:            ->with(['category', 'author'])
app/Http/Controllers/PostController.php:90:            ->with('success', 'Post created successfully.');
app/Http/Controllers/PostController.php:127:            ->with('success', 'Post updated successfully.');
app/Http/Controllers/PostController.php:138:            ->with('success', 'Post deleted successfully.');
