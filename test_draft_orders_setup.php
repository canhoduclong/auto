<?php
/**
 * Test Draft Orders Feature Setup
 * Verify routes, controller methods, and model constants
 */

$issues = [];

// 1. Check TextOrderDraft model constants
try {
    $class = 'App\Models\TextOrderDraft';
    if (!defined("$class::SCOPE_ADMIN_IMPORT")) {
        $issues[] = "Missing SCOPE_ADMIN_IMPORT constant in TextOrderDraft model";
    }
    if (!defined("$class::SCOPE_SALE_PRIVATE")) {
        $issues[] = "Missing SCOPE_SALE_PRIVATE constant in TextOrderDraft model";
    }
} catch (Exception $e) {
    $issues[] = "Error checking TextOrderDraft model: " . $e->getMessage();
}

// 2. Check TextOrderImportController methods
try {
    $controller = 'App\Http\Controllers\Admin\TextOrderImportController';
    $reflection = new ReflectionClass($controller);
    
    $requiredMethods = [
        'index', 'saleIndex', 'parse', 'saleParse',
        'confirm', 'saleConfirm', 'bulkConfirm', 'saleBulkConfirm',
        'copy', 'saleCopy', 'copyConfirm', 'saleCopyConfirm',
        'destroy', 'saleDestroy'
    ];
    
    foreach ($requiredMethods as $method) {
        if (!$reflection->hasMethod($method)) {
            $issues[] = "Missing method '$method' in TextOrderImportController";
        }
    }
} catch (Exception $e) {
    $issues[] = "Error checking controller: " . $e->getMessage();
}

// 3. Check routes exist
try {
    $routes = app('router')->getRoutes()->get('POST');
    $routeNames = collect($routes)->pluck('name')->toArray();
    
    $requiredRoutes = [
        'pages.my_draft_orders.index',
        'pages.my_draft_orders.parse',
        'pages.my_draft_orders.bulk_confirm',
        'pages.my_draft_orders.confirm',
        'pages.my_draft_orders.copy',
        'pages.my_draft_orders.copy_confirm',
        'pages.my_draft_orders.destroy',
        'admin.text-order-import.index',
        'admin.text-order-import.parse',
    ];
    
    foreach ($requiredRoutes as $routeName) {
        if (!in_array($routeName, $routeNames)) {
            $issues[] = "Missing route: $routeName";
        }
    }
} catch (Exception $e) {
    $issues[] = "Error checking routes: " . $e->getMessage();
}

// Output results
if (empty($issues)) {
    echo "✓ All checks passed! Draft Orders feature is properly set up.\n";
} else {
    echo "✗ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - " . $issue . "\n";
    }
}

echo "\nFeature URLs:\n";
echo "- Admin: /admin/text-order-import\n";
echo "- Sale: /my-dashboard/draft-orders\n";
