<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Observers\CustomerObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
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

        Product::observe(ProductObserver::class);
        Customer::observe(CustomerObserver::class);
        Order::observe(OrderObserver::class);

        Relation::morphMap([
            'product'           => \App\Models\Product::class,
            'post'              => \App\Models\Post::class,
            'user'              => \App\Models\User::class,
            'role'              => \App\Models\Role::class,
            'productvariant'    => \App\Models\ProductVariant::class,
        ]);
    }
}
