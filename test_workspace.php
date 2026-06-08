<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::whereHas('roles', function($q) { $q->where('name', 'Shipper'); })->first();
$workspaceService = app(\App\Services\UserWorkspaceService::class);
$roles = $user->roles->all();

foreach ($roles as $role) {
    echo "Role: " . $role->name . " Layout: " . $role->layout_slug . "\n";
    $roleName = strtolower(trim((string) ($role->name ?? '')));
    $layoutSlug = strtolower(trim((string) ($role->layout_slug ?? '')));
    echo "Norm Role: $roleName Layout: $layoutSlug \n";
    
    $catalog = $workspaceService->catalog();
    if (!isset($catalog[$layoutSlug])) echo "Not in catalog\n";
    else {
        $catalogItem = $catalog[$layoutSlug];
        echo "Catalog route: " . $catalogItem['route'] . "\n";
        if (!\Illuminate\Support\Facades\Route::has($catalogItem['route'])) {
            echo "Route not found\n";
        } else {
            echo "Route exists!\n";
        }
    }
}
echo json_encode($workspaceService->availableForUser($user));
