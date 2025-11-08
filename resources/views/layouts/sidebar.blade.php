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


						 
					</div>
				</div>
				<!-- /customers --> 

				<!-- Main navigation -->
				<div class="sidebar-section">
					<ul class="nav nav-sidebar" data-nav-type="accordion">

						<!-- Main -->
						<li class="nav-item-header">
							<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Main</div>
							<i class="ph-dots-three sidebar-resize-show"></i>
						</li>
						<li class="nav-item">
							<a href="{{ route('dashboard') }}" class="nav-link active">
								<i class="ph-house"></i>
								<span>
									{{ __('menu.dashboard') }}
									<span class="d-block fw-normal opacity-50">No pending orders</span>
								</span>
							</a>
						</li>
						<li class="nav-item nav-item-submenu">
							<a href="{{ route('admin.posts.index') }}" class="nav-link">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.blog') }}</span>
							</a> 
						</li>
						<li class="nav-item">
							<a href="{{ route('admin.pages.index') }}" class="nav-link">
								<i class="ph-file-text"></i>
								<span>{{ __('menu.pages') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('media.index' ) }}" class="nav-link{{ request()->routeIs('media.*') ? ' active' : '' }}">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.media') }}</span>
							</a>
						</li>
						<li class="nav-item nav-item-submenu">
							<a href="{{ route('admin.post-categories.index') }}" class="nav-link"><span>{{ __('menu.categories') }}</span></a>
						</li>
						
						<li class="nav-item ">
							<a href="{{ route('permissions.index') }}" class="nav-link">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.permissions') }}</span>
							</a>
						</li>
						
						<li class="nav-item ">
							<a href="{{ route('roles.index') }}" class="nav-link">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.roles') }}</span>
							</a>
						</li>
						
						<li class="nav-item ">
							<a href="{{ route('users.index') }}" class="nav-link">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.users') }}</span>
							</a>
						</li>
						                        <li class="nav-item ">
													<a href="{{ route('categories.index') }}" class="nav-link{{ request()->routeIs('categories.*') ? ' active' : '' }}">
														<i class="ph-note-blank"></i>
														<span>{{ __('menu.categories') }}</span>
													</a>
												</li>
												<li class="nav-item ">
													<a href="{{ route('admin.brands.index') }}" class="nav-link{{ request()->routeIs('admin.brands.*') ? ' active' : '' }}">
														<i class="ph-note-blank"></i>
														<span>{{ __('menu.brands') }}</span>
													</a>
												</li>						<li class="nav-item ">
							<a href="{{ route('products.index') }}" class="nav-link{{ request()->routeIs('products.*') ? ' active' : '' }}">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.products') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('product-variants.index') }}" class="nav-link{{ request()->routeIs('product-variants.*') ? ' active' : '' }}">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.product_variants') }}</span>
							</a>
						</li>
						
						<li class="nav-item ">
							<a href="{{ route('warehouses.index') }}" class="nav-link{{ request()->routeIs('warehouses.*') ? ' active' : '' }}">
								<i class="ph-storefront"></i>
								<span>{{ __('menu.warehouses') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('inventories.index') }}" class="nav-link{{ request()->routeIs('inventories.*') ? ' active' : '' }}">
								<i class="ph-package"></i>
								<span>{{ __('menu.inventories') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('inventory-movements.index') }}" class="nav-link{{ request()->routeIs('inventory-movements.*') ? ' active' : '' }}">
								<i class="ph-arrows-left-right"></i>
								<span>{{ __('menu.inventory_movements') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('inventory-documents.index') }}" class="nav-link{{ request()->routeIs('inventory-documents.*') ? ' active' : '' }}">
								<i class="ph-files"></i>
								<span>{{ __('menu.inventory_documents') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('inventory-adjustments.index') }}" class="nav-link{{ request()->routeIs('inventory-adjustments.*') ? ' active' : '' }}">
								<i class="ph-wrench"></i>
								<span>{{ __('menu.inventory_adjustments') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('inventory-reservations.index') }}" class="nav-link{{ request()->routeIs('inventory-reservations.*') ? ' active' : '' }}">
								<i class="ph-timer"></i>
								<span>{{ __('menu.inventory_reservations') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('customertype.index') }}" class="nav-link{{ request()->routeIs('customertype.*') ? ' active' : '' }}">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.customer_type') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('customers.index') }}" class="nav-link{{ request()->routeIs('customers.*') ? ' active' : '' }}">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.customers') }}</span>
							</a>
						</li>						
						<li class="nav-item ">
							<a href="{{ route('customers.addresses.list' ) }}" class="nav-link">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.customer_address') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('companies.index') }}" class="nav-link{{ request()->routeIs('companies.*') ? ' active' : '' }}">
								<i class="ph-buildings"></i>
								<span>{{ __('menu.companies') }}</span>
							</a>
						</li>						
						
						<li class="nav-item ">
							<a href="{{ route('orders.index') }}" class="nav-link{{ request()->routeIs('orders.*') ? ' active' : '' }}">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.orders') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('order-returns.index') }}" class="nav-link{{ request()->routeIs('order-returns.*') ? ' active' : '' }}">
								<i class="ph-arrow-fat-lines-right"></i>
								<span>{{ __('menu.order_returns') }}</span>
							</a>
						</li>
						<li class="nav-item ">
							<a href="{{ route('transactions.index') }}" class="nav-link{{ request()->routeIs('transactions.*') ? ' active' : '' }}">
								<i class="ph-note-blank"></i>
								<span>{{ __('menu.transactions') }}</span>
							</a>
						</li>

						<li class="nav-item">
							<a href="{{ route('admin.settings.index') }}" class="nav-link{{ request()->routeIs('admin.settings.index') ? ' active' : '' }}">
								<i class="ph-gear"></i>
								<span>{{ __('menu.settings') }}</span>
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