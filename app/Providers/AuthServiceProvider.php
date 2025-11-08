<?php

namespace App\Providers; 
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Models\Product;
use App\Policies\ProductPolicy;

use App\Models\Category;
use App\Policies\CategoryPolicy;

use App\Models\Customer;
use App\Policies\CustomerPolicy;

use App\Models\Permission;
use App\Policies\PermissionPolicy;

use App\Models\Media;
use App\Policies\MediaPolicy;

use App\Models\Role;
use App\Policies\RolePolicy;

use App\Models\Order;
use App\Policies\OrderPolicy;

use App\Models\ProductVariant;
use App\Policies\ProductVariantPolicy;

use App\Models\Company;
use App\Policies\CompanyPolicy;



class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
        Customer::class => CustomerPolicy::class,
        Product::class => ProductPolicy::class,
        Media::class => MediaPolicy::class,
        Role::class => RolePolicy::class,
        Order::class => OrderPolicy::class,
        ProductVariant::class => ProductVariantPolicy::class,
        Company::class => CompanyPolicy::class,
        
        //Permission::class => PermissionPolicy::class,


    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Đăng ký các Gates tại đây
        // Gate::define('edit-settings', function (User $user) {
        //     return $user->isAdmin();
        // });
        Gate::define('filter_customer_by_user', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('bulk-delete', function (User $user, $model) {
            if ($model === ProductVariant::class) {
                return $user->hasPermissionTo('product-variants.bulk-delete');
            }
            return false;
        });
    }
}