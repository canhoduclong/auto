<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/roles/9',
        'PUT',
        [
            'name' => 'Shipper',
            'layout_slug' => 'website_shipper',
            '_token' => csrf_token() // Need to bypass CSRF, or just use artisan tinker
        ]
    )
);
echo $response->getContent();
