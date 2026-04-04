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
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        $agent = strtolower((string) Request::userAgent());
        $isMobileClient = preg_match('/iphone|ipod|android|blackberry|opera mini|windows phone|mobile|webos|iemobile|ipad/', $agent) === 1;
        View::share('isMobileClient', $isMobileClient);

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
