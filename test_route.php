<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = \App\Models\Role::find(9);
echo "Before: " . $role->layout_slug . "\n";

$role->update(['layout_slug' => 'website_shipper']);

echo "After: " . \App\Models\Role::find(9)->layout_slug . "\n";
