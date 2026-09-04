<!-- Sidebar header -->
			<div class="sidebar-section bg-black bg-opacity-10 border-bottom border-bottom-white border-opacity-10">
				<div class="sidebar-logo d-flex justify-content-center align-items-center">
					<a href="index.html" class="d-inline-flex align-items-center py-2">
						<img src="/assets/images/logo_icon.svg" class="sidebar-logo-icon" alt="">
						<img src="/assets/images/logo_text_light.svg" class="sidebar-resize-hide ms-3" height="14" alt="">
					</a>

					<div class="sidebar-resize-hide ms-auto">
						<button type="button" class="btn btn-flat-white btn-icon btn-sm rounded-pill border-transparent sidebar-control sidebar-main-resize d-none d-lg-inline-flex">
							<i class="ph-arrows-left-right"></i>
						</button>

						<button type="button" class="btn btn-flat-white btn-icon btn-sm rounded-pill border-transparent sidebar-mobile-main-toggle d-lg-none">
							<i class="ph-x"></i>
						</button>
					</div>
				</div>
			</div>
			<!-- /sidebar header -->


			<!-- Sidebar content -->
			<div class="sidebar-content">

				<!-- Customers -->
				<div class="sidebar-section sidebar-resize-hide dropdown mx-2">
					<a href="#" class="btn btn-link text-body text-start lh-1 dropdown-toggle p-2 my-1 w-100" data-bs-toggle="dropdown" data-color-theme="dark">
						<div class="hstack gap-2 flex-grow-1 my-1">
                            @if(isset(Auth::user()->avatar))
							<img src="/{{ Auth::user()->avatar }}" class="w-32px h-32px rounded-pill" alt="">
                            @else
							<img src="/assets/images/brands/shell.svg" class="w-32px h-32px" alt="">
                            @endif
							<div class="me-auto">
								<div class="fs-sm text-white opacity-75 mb-1">{{ auth()->user()->roles()->first()->name ?? '' }}</div>
								<div class="fw-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
							</div>
						</div>
					</a>

					<div class="dropdown-menu w-100">
						
										<!-- Thông tin User -->
					<div class="p-4 border-b border-gray-700 items-center">
						 
						<div>
							<div class="font-semibold">{{ auth()->user()->name }}</div>
							<div class="text-sm text-gray-400">{{ auth()->user()->email }}</div>
							<div class="text-xs text-gray-500">
								@if(auth()->user()->roles->isNotEmpty())
									{{ auth()->user()->roles->pluck('name')->join(', ') }}
								@else
									No Role
								@endif
							</div>
						</div>

                        <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm mt-2">Profile</a>

						<form method="POST" action="{{ route('logout') }}" class="mt-2">
							@csrf
							<button class="w-full bg-red-600 px-4 py-2 rounded hover:bg-red-700">
								{{ __('menu.logout') }}
							</button>
						</form>

					</div>

					<!-- Role Switcher -->
					@if(auth()->user()->roles->count() > 1)
					<div class="p-3 border-t border-gray-700">
						<div class="text-xs font-semibold mb-2 text-gray-400">CHỌN VAI TRÒ</div>
						<div class="d-flex flex-wrap gap-2">
							@foreach(auth()->user()->roles as $role)
								@php
									$roleName = strtolower((string) $role->name);
									$isActive = session('active_role') === $role->name || 
										(session()->missing('active_role') && auth()->user()->roles->first()->name === $role->name);
									$roleLabel = match ($roleName) {
										'account', 'accountant', 'accounting' => 'Kế toán',
										'package' => 'Đóng hàng',
										'warehouse' => 'Kho',
										'manager_shipper' => 'Điều phối ship',
										default => ucfirst($role->name),
									};
								@endphp
								<form action="{{ route('role.switch', $role->name) }}" method="POST" class="inline-block">
									@csrf
									<button type="submit" 
										class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
										title="Chuyển sang vai trò {{ $role->name }}">
										{{ $roleLabel }}
									</button>
								</form>
							@endforeach
						</div>
					</div>
					@endif
						 
					</div>
				</div>
				<!-- /customers --> 

				<!-- Main navigation -->
				<div class="sidebar-section">
					<ul class="nav nav-sidebar" data-nav-type="accordion">

						<!-- Tổng quan -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Tổng quan</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('dashboard') }}" class="nav-link{{ request()->routeIs('dashboard') ? ' active' : '' }}">
								<i class="ph-house"></i>
								<span>{{ __('menu.dashboard') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('media.index') }}" class="nav-link{{ request()->routeIs('media.*') ? ' active' : '' }}">
								<i class="ph-images"></i>
								<span>{{ __('menu.media') }}</span>
							</a>
						</li>

						<!-- Đơn hàng -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Đơn hàng</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('orders.index') }}" class="nav-link{{ request()->routeIs('orders.*') ? ' active' : '' }}">
								<i class="ph-shopping-cart"></i>
								<span>Đơn hàng</span>
							</a>
						</li>
					<li class="nav-item">
						<a href="{{ (auth()->user()?->isAdmin() || auth()->user()?->isSalesFlowRole() || auth()->user()?->hasPermission('orders.monitoring')) ? route('pages.my_orders.monitoring', ['tab' => 'drafts']) : route('pages.my_order_drafts') }}" class="nav-link{{ request()->routeIs('pages.my_order_drafts*') ? ' active' : '' }}">
							<i class="ph-textbox"></i>
							<span>Đơn nháp</span>
						</a>
					</li>
						@if(auth()->user()?->hasRole('admin'))
						<li class="nav-item">
							<a href="{{ route('admin.text-order-import.index') }}" class="nav-link{{ request()->routeIs('admin.text-order-import.*') ? ' active' : '' }}">
								<i class="ph-textbox"></i>
								<span>Nhập đơn text</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.daily-rebuild.index') }}" class="nav-link{{ request()->routeIs('admin.daily-rebuild.*') ? ' active' : '' }}">
								<i class="ph-arrow-counter-clockwise"></i>
								<span>Làm lại nguyên ngày</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.imported-sales-orders.index') }}" class="nav-link{{ request()->routeIs('admin.imported-sales-orders.*') ? ' active' : '' }}">
								<i class="ph-clipboard-text"></i>
								<span>Hoàn chỉnh đơn lịch sử</span>
							</a>
						</li>
						@endif
						<li class="nav-item">
							<a href="{{ route('order-returns.index') }}" class="nav-link{{ request()->routeIs('order-returns.*') ? ' active' : '' }}">
								<i class="ph-arrow-fat-lines-left"></i>
								<span>{{ __('menu.order_returns') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.order-schedule-runs.index') }}" class="nav-link{{ request()->routeIs('admin.order-schedule-runs.*') ? ' active' : '' }}">
								<i class="ph-calendar-check"></i>
								<span>Đơn tự động</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('approval-workflows.index') }}" class="nav-link{{ request()->routeIs('approval-workflows.*') ? ' active' : '' }}">
								<i class="ph-flow-arrow"></i>
								<span>Quy trình duyệt</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('task-assignments.index') }}" class="nav-link{{ request()->routeIs('task-assignments.*') ? ' active' : '' }}">
								<i class="ph-clipboard-text"></i>
								<span>Giao Việc</span>
							</a>
						</li>
						@if(auth()->user()?->hasRole('admin'))
						<li class="nav-item">
							<a href="{{ route('task-delegate-configs.index') }}" class="nav-link{{ request()->routeIs('task-delegate-configs.*') ? ' active' : '' }}">
								<i class="ph-user-gear"></i>
								<span>Phan Quyen Giao Viec</span>
							</a>
						</li>
						@endif

						@if(auth()->user()?->isAdmin())
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Tài chính</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.accounting.cashflow') }}" class="nav-link{{ request()->routeIs('admin.accounting.cashflow') || request()->routeIs('admin.accounting.cashflow.*') || request()->routeIs('admin.accounting.refresh-history') ? ' active' : '' }}">
								<i class="ph-currency-circle-dollar"></i>
								<span>Thu/chi</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.accounting.transactions.create') }}" class="nav-link{{ request()->routeIs('admin.accounting.transactions.*') ? ' active' : '' }}">
								<i class="ph-plus-circle"></i>
								<span>Tạo giao dịch</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.accounting.financial-reports') }}" class="nav-link{{ request()->routeIs('admin.accounting.financial-reports') ? ' active' : '' }}">
								<i class="ph-chart-line-up"></i>
								<span>Báo cáo tài chính</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.accounting.accounts.index') }}" class="nav-link{{ request()->routeIs('admin.accounting.accounts.*') ? ' active' : '' }}">
								<i class="ph-wallet"></i>
								<span>Tài khoản</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.accounting.transaction-categories.index') }}" class="nav-link{{ request()->routeIs('admin.accounting.transaction-categories.*') ? ' active' : '' }}">
								<i class="ph-tree-structure"></i>
								<span>Quản trị danh mục giao dịch</span>
							</a>
						</li>
						@elseif(auth()->user()?->hasRole('account') || auth()->user()?->hasRole('accountant') || auth()->user()?->hasRole('accounting'))
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Kế toán</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('accounting.dashboard') }}" class="nav-link{{ request()->routeIs('accounting.dashboard') ? ' active' : '' }}">
								<i class="ph-currency-circle-dollar"></i>
								<span>Dashboard Kế toán</span>
							</a>
						</li>
						@endif

						<!-- Khách hàng -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Khách hàng</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('customers.index') }}" class="nav-link{{ request()->routeIs('customers.*') ? ' active' : '' }}">
								<i class="ph-users"></i>
								<span>{{ __('menu.customers') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('customertype.index') }}" class="nav-link{{ request()->routeIs('customertype.*') ? ' active' : '' }}">
								<i class="ph-tag"></i>
								<span>{{ __('menu.customer_type') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('customers.addresses.list') }}" class="nav-link">
								<i class="ph-map-pin-simple-area"></i>
								<span>{{ __('menu.customer_address') }}</span>
							</a>
						</li>

						<!-- Sản phẩm & Danh mục -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Sản phẩm</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('products.index') }}" class="nav-link{{ request()->routeIs('products.*') ? ' active' : '' }}">
								<i class="ph-package"></i>
								<span>{{ __('menu.products') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('product-variants.index') }}" class="nav-link{{ request()->routeIs('product-variants.*') ? ' active' : '' }}">
								<i class="ph-circles-four"></i>
								<span>{{ __('menu.product_variants') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('categories.index') }}" class="nav-link{{ request()->routeIs('categories.*') ? ' active' : '' }}">
								<i class="ph-folder-open"></i>
								<span>{{ __('menu.categories') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.brands.index') }}" class="nav-link{{ request()->routeIs('admin.brands.*') ? ' active' : '' }}">
								<i class="ph-seal-check"></i>
								<span>{{ __('menu.brands') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.suppliers.index') }}" class="nav-link{{ request()->routeIs('admin.suppliers.*') ? ' active' : '' }}">
								<i class="ph-factory"></i>
								<span>Nhà cung cấp</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('warehouse.supplier-prices.index') }}" class="nav-link{{ request()->routeIs('warehouse.supplier-prices.*') ? ' active' : '' }}">
								<i class="ph-currency-circle-dollar"></i>
								<span>Bảng giá thu mua</span>
							</a>
						</li>

						<!-- Kho hàng -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Kho hàng</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('warehouses.index') }}" class="nav-link{{ request()->routeIs('warehouses.*') ? ' active' : '' }}">
								<i class="ph-storefront"></i>
								<span>Quản trị kho</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('inventory-documents.index') }}" class="nav-link{{ request()->routeIs('inventory-documents.*') ? ' active' : '' }}">
								<i class="ph-files"></i>
								<span>Nhập xuất kho</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.warehouse-dispatch-slips.index') }}" class="nav-link{{ request()->routeIs('admin.warehouse-dispatch-slips.*') ? ' active' : '' }}">
								<i class="ph-file-text"></i>
								<span>Phiếu xuất kho tổng</span>
							</a>
						</li>
						@if(auth()->user()?->isAdmin())
						<li class="nav-item">
							<a href="{{ route('admin.google-sheet-inventory-reset.index') }}" class="nav-link{{ request()->routeIs('admin.google-sheet-inventory-reset.*') ? ' active' : '' }}">
								<i class="ph-arrow-counter-clockwise"></i>
								<span>Reset tồn kho Google Sheet</span>
							</a>
						</li>
						@endif
						<li class="nav-item">
							<a href="{{ route('inventories.index') }}" class="nav-link{{ request()->routeIs('inventories.*') ? ' active' : '' }}">
								<i class="ph-chart-bar"></i>
								<span>Báo cáo tồn kho</span>
							</a>
						</li>

						@if(auth()->user()?->hasRole('warehouse') || auth()->user()?->hasRole('admin'))
						<li class="nav-item">
							<a href="{{ route('tasks.my-tasks') }}" class="nav-link{{ request()->routeIs('tasks.my-tasks') || request()->routeIs('task-assignments.assigned-to-me') ? ' active' : '' }}">
								<i class="ph-clipboard-text"></i>
								<span>Nhiệm vụ</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('task-assignments.in-progress') }}" class="nav-link{{ request()->routeIs('task-assignments.in-progress') || request()->routeIs('task-assignments.complete-form') ? ' active' : '' }}">
								<i class="ph-check-circle"></i>
								<span>Thực hiện</span>
							</a>
						</li>
						@endif

						<!-- Nhà xe -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Nhà xe</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.truck-brands.index') }}" class="nav-link{{ request()->routeIs('admin.truck-brands.*') ? ' active' : '' }}">
								<i class="ph-buildings"></i>
								<span>Nhà xe (Brands)</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.truck-stations.index') }}" class="nav-link{{ request()->routeIs('admin.truck-stations.*') ? ' active' : '' }}">
								<i class="ph-map-pin"></i>
								<span>Quản lý Trạm xe</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.truck-routes.index') }}" class="nav-link{{ request()->routeIs('admin.truck-routes.*') ? ' active' : '' }}">
								<i class="ph-path"></i>
								<span>Quản lý Tuyến đi</span>
							</a>
						</li>

						<!-- Nội dung & CMS -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Nội dung</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.posts.index') }}" class="nav-link{{ request()->routeIs('admin.posts.*') ? ' active' : '' }}">
								<i class="ph-article"></i>
								<span>{{ __('menu.blog') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.pages.index') }}" class="nav-link{{ request()->routeIs('admin.pages.*') ? ' active' : '' }}">
								<i class="ph-file-text"></i>
								<span>{{ __('menu.pages') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.hoang-long-profile.edit') }}" class="nav-link{{ request()->routeIs('admin.hoang-long-profile.*') ? ' active' : '' }}">
								<i class="ph-buildings"></i>
								<span>Profile Hoàng Long</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.post-categories.index') }}" class="nav-link{{ request()->routeIs('admin.post-categories.*') ? ' active' : '' }}">
								<i class="ph-folder-notch-open"></i>
								<span>Danh mục bài viết</span>
							</a>
						</li>

						<!-- Hệ thống -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Hệ thống</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('users.index') }}" class="nav-link{{ request()->routeIs('users.*') ? ' active' : '' }}">
								<i class="ph-user-gear"></i>
								<span>Quản trị users</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('roles.index') }}" class="nav-link{{ request()->routeIs('roles.*') ? ' active' : '' }}">
								<i class="ph-shield-star"></i>
								<span>{{ __('menu.roles') }}</span>
							</a>
						</li>
						@if(auth()->user()?->isAdmin())
						<li class="nav-item">
							<a href="{{ route('admin.notifications.index') }}" class="nav-link{{ request()->routeIs('admin.notifications.*') ? ' active' : '' }}">
								<i class="ph-bell-ringing"></i>
								<span>Quản trị thông báo</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('layouts.index') }}" class="nav-link{{ request()->routeIs('layouts.*') ? ' active' : '' }}">
								<i class="ph-layout"></i>
								<span>Layout</span>
							</a>
						</li>
						@endif
						<li class="nav-item">
							<a href="{{ route('permissions.index') }}" class="nav-link{{ request()->routeIs('permissions.*') ? ' active' : '' }}">
								<i class="ph-lock-key"></i>
								<span>{{ __('menu.permissions') }}</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('teams.index') }}" class="nav-link{{ request()->routeIs('teams.*') ? ' active' : '' }}">
								<i class="ph-users-three"></i>
								<span>Teams</span>
							</a>
						</li>
						@if(auth()->user()?->isAdmin())
						<li class="nav-item">
							<a href="{{ route('admin.organization-units.index') }}" class="nav-link{{ request()->routeIs('admin.organization-units.*') ? ' active' : '' }}">
								<i class="ph-buildings"></i>
								<span>Khối & phòng ban</span>
							</a>
						</li>
						@endif
						<li class="nav-item">
							<a href="{{ route('provinces.index') }}" class="nav-link{{ request()->routeIs('provinces.*') ? ' active' : '' }}">
								<i class="ph-map-trifold"></i>
								<span>Tỉnh/Thành & Phường/Xã</span>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.settings.index') }}" class="nav-link{{ request()->routeIs('admin.settings.index') ? ' active' : '' }}">
								<i class="ph-gear"></i>
								<span>{{ __('menu.settings') }}</span>
							</a>
						</li>
						@if(auth()->user()?->isAdmin())
						<li class="nav-item">
							<a href="{{ route('admin.order-fee-types.index') }}" class="nav-link{{ request()->routeIs('admin.order-fee-types.*') ? ' active' : '' }}">
								<i class="ph-receipt"></i>
								<span>Quản trị phí đơn hàng</span>
							</a>
						</li>
						@endif
						<li class="nav-item">
							<a href="{{ route('admin.settings.reset-data.index') }}" class="nav-link{{ request()->routeIs('admin.settings.reset-data.*') ? ' active' : '' }}">
								<i class="ph-database"></i>
								<span>Reset Data</span>
							</a>
						</li>

					</ul>
					 <!-- Logout -->
					<div class="p-4 border-t border-gray-700">
						
					</div>

				</div>
				<!-- /main navigation -->

			</div>
			<!-- /sidebar content -->
