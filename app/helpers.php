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
        $isSale = $user->hasRole('sale');
        $isShipper = $user->hasRole('shipper');
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

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
        return 'layouts.accounting';
    }
}
