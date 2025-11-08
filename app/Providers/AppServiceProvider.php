<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        Relation::morphMap([
            'product'           => \App\Models\Product::class,
            'post'              => \App\Models\Post::class,
            'user'              => \App\Models\User::class,
            'role'              => \App\Models\Role::class,
            'productvariant'    => \App\Models\ProductVariant::class,
        ]);
    }
}
