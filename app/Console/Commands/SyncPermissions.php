<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\Permission;

class SyncPermissions extends Command
{
    
    protected $signature = 'app:sync-permissions'; 
    // protected $signature = 'permissions:sync';
    protected $description = 'Đồng bộ route list vào bảng permissions';

    public function handle()
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'name'   => $route->getName(),
                'uri'    => $route->uri(),
                'method' => implode('|', $route->methods()),
                'action' => $route->getActionName(),
            ];
        })->filter(fn($r) => !empty($r['name'])); // chỉ sync route có name

        foreach ($routes as $route) {
            Permission::updateOrCreate(
                ['name' => $route['name']], // key chính
                [
                    'uri'    => $route['uri'],
                    'method' => $route['method'],
                    'action' => $route['action'],
                ]
            );
        }

        $this->info("Đã sync " . $routes->count() . " routes vào bảng permissions.");
    }
}
