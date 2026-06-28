<?php
if (!function_exists('getWarehouseNotifications')) {
    /**
     * Lấy danh sách thông báo nghiệp vụ kho cho user hiện tại (dùng chung cho mọi layout)
     * @param \App\Models\User $user
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    function getWarehouseNotifications($user, $limit = 7)
    {
        $warehouseId = $user->warehouse_id;
        $notifications = collect();
        $isWarehouse = $user->hasRole('warehouse');
        $isPackage = $user->hasRole('package');
        $isSale = $user->hasRole('sale');
        $isShipper = $user->hasRole('shipper');
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        // Đơn mới đã duyệt, chờ kho xử lý
        $newOrders = \App\Models\Order::query()
            ->with(['customer', 'user', 'items.product', 'items.variant'])
            ->when($warehouseId, fn ($query) => $query->where(function ($scope) use ($warehouseId) {
                $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->whereIn('status', [\App\Models\Order::STATUS_APPROVED, \App\Models\Order::STATUS_READY_TO_PACK])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
        foreach ($newOrders as $order) {
            $sequence = $order->daily_sequence ?: $order->id;
            $notifications->push([
                'type' => 'new_order',
                'title' => 'Đơn mới: #' . $sequence . ' - ' . ($order->customer?->name ?: 'Khách hàng'),
                'meta' => 'Sale: ' . ($order->user?->name ?: 'Chưa xác định') . ' • Giờ tạo: ' . optional($order->created_at)->format('H:i d/m/Y'),
                'details' => $order->items->map(fn ($item) => [
                    'name' => $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm',
                    'quantity' => (float) ($item->quantity ?? 0),
                    'price' => (float) ($item->price ?? 0),
                    'line_total' => (float) ($item->total ?? ((float) ($item->quantity ?? 0) * (float) ($item->price ?? 0))),
                ])->values()->all(),
                'total' => (float) ($order->total ?? 0),
                'note' => (string) ($order->note ?? ''),
                'link' => route($isPackage ? 'package.orders' : 'warehouse.orders', ['date' => optional($order->created_at)->toDateString(), 'highlight' => $order->id]),
                'time' => optional($order->created_at)->format('d/m/Y H:i'),
            ]);
        }

        // Đơn đã đóng gói, chờ shipper nhận
        $packedOrders = \App\Models\Order::query()
            ->with(['customer', 'user'])
            ->where('warehouse_id', $warehouseId)
            ->where('status', \App\Models\Order::STATUS_READY_TO_SHIP)
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get();
        foreach ($packedOrders as $order) {
            $orderCode = $order->code ? '#' . $order->code : '#' . $order->id;
            if ($isWarehouse) {
                $title = 'Đơn ' . $orderCode . ' đã đóng gói, chờ Shipper nhận';
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('warehouse.orders') . '?highlight=' . $order->id;
            } elseif ($isShipper) {
                $title = 'Kho đã đóng gói đơn ' . $orderCode . ', chờ bạn nhận';
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('shipper.available') . '?highlight=' . $order->id;
            } else {
                $title = 'Đơn ' . $orderCode . ' đã đóng gói';
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('pages.my_orders') . '?highlight=' . $order->id;
            }
            $notifications->push([
                'type' => 'warehouse',
                'title' => $title,
                'meta' => $meta,
                'link' => $link,
                'time' => optional($order->updated_at)->format('d/m/Y H:i'),
            ]);
        }

        // Sale: Phản hồi yêu cầu thay đổi đơn hàng từ Nhà máy
        $saleConfirmOrders = \App\Models\Order::query()
            ->with(['customer', 'user'])
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_adjustment_status', \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED)
            ->orderByDesc('warehouse_adjustment_confirmed_at')
            ->limit(2)
            ->get();
        foreach ($saleConfirmOrders as $order) {
            $orderCode = $order->code ? '#' . $order->code : '#' . $order->id;
            $saleName = $order->user?->name ?: 'Sale';
            if ($isWarehouse) {
                $title = 'Có phản hồi về đơn ' . $orderCode . ' từ ' . $saleName;
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('warehouse.orders') . '?highlight=' . $order->id;
            } elseif ($isSale) {
                $title = 'Đã gửi phản hồi về đơn ' . $orderCode . ' tới Kho';
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('pages.my_orders') . '?highlight=' . $order->id;
            } else {
                $title = 'Sale ' . $saleName . ' đã phản hồi đơn ' . $orderCode;
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('pages.my_orders') . '?highlight=' . $order->id;
            }
            $notifications->push([
                'type' => 'sale',
                'title' => $title,
                'meta' => $meta,
                'link' => $link,
                'time' => optional($order->warehouse_adjustment_confirmed_at)->format('d/m/Y H:i'),
            ]);
        }

        // Sale: Gửi yêu cầu thay đổi đơn hàng tới kho, cần phê duyệt
        $pendingSaleConfirmOrders = \App\Models\Order::query()
            ->with(['customer', 'user'])
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_adjustment_status', \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION)
            ->orderByDesc('warehouse_adjustment_requested_at')
            ->limit(2)
            ->get();
        foreach ($pendingSaleConfirmOrders as $order) {
            $orderCode = $order->code ? '#' . $order->code : '#' . $order->id;
            $warehouseName = $order->warehouse?->name ?: 'Kho';
            if ($isWarehouse) {
                $title = 'Đã gửi yêu cầu thay đổi đơn hàng ' . $orderCode . ' tới Sale';
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('warehouse.orders') . '?highlight=' . $order->id;
            } elseif ($isSale) {
                $title = $warehouseName . ' yêu cầu thay đổi đơn hàng ' . $orderCode;
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('pages.my_orders') . '?highlight=' . $order->id;
            } else {
                $title = 'Kho đã gửi yêu cầu thay đổi đơn hàng ' . $orderCode;
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('pages.my_orders') . '?highlight=' . $order->id;
            }
            $notifications->push([
                'type' => 'sale',
                'title' => $title,
                'meta' => $meta,
                'link' => $link,
                'time' => optional($order->warehouse_adjustment_requested_at)->format('d/m/Y H:i'),
            ]);
        }

        // Shipper: khách trả hàng cần nhận hàng
        $returnOrders = \App\Models\Order::query()
            ->with(['customer', 'shipper'])
            ->where('warehouse_id', $warehouseId)
            ->where('status', \App\Models\Order::STATUS_RETURNED)
            ->orderByDesc('updated_at')
            ->limit(2)
            ->get();
        foreach ($returnOrders as $order) {
            $orderCode = $order->code ? '#' . $order->code : '#' . $order->id;
            $shipperName = $order->shipper?->name ?: 'Shipper';
            if ($isWarehouse) {
                $title = $shipperName . ' trả hàng đơn ' . $orderCode . ', cần nhận hàng';
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('warehouse.returns') . '?highlight=' . $order->id;
            } elseif ($isShipper) {
                $title = 'Bạn cần nhận hàng trả về đơn ' . $orderCode;
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('shipper.my-orders') . '?highlight=' . $order->id;
            } else {
                $title = 'Khách trả hàng đơn ' . $orderCode;
                $meta = $order->customer?->name ?: 'Khách hàng';
                $link = route('pages.my_orders') . '?highlight=' . $order->id;
            }
            $notifications->push([
                'type' => 'shipper',
                'title' => $title,
                'meta' => $meta,
                'link' => $link,
                'time' => optional($order->updated_at)->format('d/m/Y H:i'),
            ]);
        }

        // Có thể mở rộng thêm các loại thông báo khác ở đây...
        // Sắp xếp theo thời gian mới nhất, lấy tối đa $limit
        return $notifications->sortByDesc('time')->take($limit)->values();
    }
}

if (!function_exists('getDepartmentBroadcastNotifications')) {
    function getDepartmentBroadcastNotifications($user, int $limit = 5, ?string $layoutKey = null)
    {
        if (!$user || !\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            return collect();
        }

        $notifications = $user->notifications()
            ->where('type', \App\Notifications\DepartmentBroadcastNotification::class)
            ->latest()
            ->limit($layoutKey !== null ? max($limit * 6, 30) : $limit)
            ->get();

        if ($layoutKey !== null && function_exists('departmentBroadcastMatchesLayout')) {
            $notifications = $notifications
                ->filter(fn ($notification) => departmentBroadcastMatchesLayout($notification->data ?? [], $layoutKey))
                ->take($limit)
                ->values();
        }

        $senderIds = $notifications
            ->pluck('data.sender_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $senders = $senderIds->isNotEmpty()
            ? \App\Models\User::query()
                ->with(['department', 'roles'])
                ->whereIn('id', $senderIds->all())
                ->get()
                ->keyBy('id')
            : collect();

        return $notifications
            ->map(function ($notification) use ($senders) {
                $senderId = (int) ($notification->data['sender_id'] ?? 0);
                $sender = $senderId > 0 ? $senders->get($senderId) : null;
                $senderRoles = $sender
                    ? $sender->roles->pluck('name')->filter()->values()->all()
                    : [];

                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Thông báo',
                    'message' => $notification->data['message'] ?? '',
                    'url' => $notification->data['url'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'target_roles' => $notification->data['target_roles'] ?? [],
                    'target_role_names' => $notification->data['target_role_names'] ?? [],
                    'sender_id' => $notification->data['sender_id'] ?? null,
                    'sender_name' => $sender?->name ?: 'Hệ thống',
                    'sender_department' => $sender?->department?->name ?: ($senderRoles ? implode(', ', $senderRoles) : 'Hệ thống'),
                    'sender_roles' => $senderRoles,
                ];
            });
    }
}

if (!function_exists('departmentBroadcastRoleAliases')) {
    function departmentBroadcastRoleAliases(): array
    {
        return [
            'CEO' => ['CEO', 'ceo'],
            'warehouse' => ['warehouse'],
            'accountant' => ['account', 'accountant', 'accounting'],
            'Shipper' => ['Shipper', 'shipper', 'manager_shipper'],
            'leader' => ['leader', 'leader_sale', 'sale_manager'],
            'manager' => ['manager', 'manager_sale'],
            'Director' => ['Director', 'director'],
            'sale' => ['sale'],
        ];
    }
}

if (!function_exists('departmentBroadcastExpandTargetRoles')) {
    function departmentBroadcastExpandTargetRoles(array $targetRoles): array
    {
        $aliases = departmentBroadcastRoleAliases();

        return collect($targetRoles)
            ->flatMap(fn ($role) => $aliases[(string) $role] ?? [(string) $role])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}

if (!function_exists('departmentBroadcastLayoutRoles')) {
    function departmentBroadcastLayoutRoles(?string $layoutKey): array
    {
        return match (strtolower((string) $layoutKey)) {
            'ceo' => ['CEO', 'ceo'],
            'director' => ['Director', 'director'],
            'accounting' => ['account', 'accountant', 'accounting'],
            'warehouse' => ['warehouse'],
            'shipper' => ['Shipper', 'shipper', 'manager_shipper'],
            'site' => ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'],
            'admin' => ['*'],
            default => [],
        };
    }
}

if (!function_exists('departmentBroadcastMatchesLayout')) {
    function departmentBroadcastMatchesLayout(array $data, ?string $layoutKey): bool
    {
        $layoutRoles = departmentBroadcastLayoutRoles($layoutKey);
        if (in_array('*', $layoutRoles, true) || $layoutRoles === []) {
            return true;
        }

        $targetRoles = $data['target_role_names'] ?? departmentBroadcastExpandTargetRoles((array) ($data['target_roles'] ?? []));
        $targetRoles = collect($targetRoles)
            ->map(fn ($role) => strtolower((string) $role))
            ->filter();

        if ($targetRoles->isEmpty()) {
            return true;
        }

        $layoutRoles = collect($layoutRoles)
            ->map(fn ($role) => strtolower((string) $role));

        return $targetRoles->intersect($layoutRoles)->isNotEmpty();
    }
}


if (!function_exists('format_kg')) {
    /**
     * Format a kg value for display.
     * Rules: remove trailing zeros, no space, append "kg".
     * Examples: 1.000 → "1kg", 1.250 → "1.25kg", 1.200 → "1.2kg"
     */
    function format_kg(float|int|string $value): string
    {
        $num = (float) $value;
        $str = rtrim(rtrim(number_format($num, 3, '.', ''), '0'), '.');
        return $str . 'kg';
    }
}

if (!function_exists('current_accounting_route_prefix')) {
    function current_accounting_route_prefix(): string
    {
        $routeName = request()->route()?->getName() ?? '';

        if (str_starts_with($routeName, 'admin.accounting.')) {
            return 'admin.accounting.';
        }
        if (str_starts_with($routeName, 'ceo.')) {
            return 'ceo.';
        }
        if (str_starts_with($routeName, 'director.')) {
            return 'director.';
        }
        return 'accounting.';
    }
}

if (!function_exists('accounting_route_name')) {
    function accounting_route_name(string $name, ?string $prefix = null): string
    {
        return rtrim($prefix ?? current_accounting_route_prefix(), '.') . '.' . ltrim($name, '.');
    }
}

if (!function_exists('accounting_route')) {
    function accounting_route(string $name, $parameters = [], bool $absolute = true, ?string $prefix = null): string
    {
        return route(accounting_route_name($name, $prefix), $parameters, $absolute);
    }
}

if (!function_exists('accounting_layout')) {
    function accounting_layout(): string
    {
        $prefix = current_accounting_route_prefix();
        if ($prefix === 'admin.accounting.') {
            return 'layouts.admin-accounting';
        }
        if ($prefix === 'ceo.') {
            return 'layouts.ceo';
        }
        if ($prefix === 'director.') {
            return 'layouts.director';
        }
        return 'layouts.accounting';
    }
}
