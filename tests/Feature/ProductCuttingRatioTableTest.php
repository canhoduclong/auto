<?php
namespace Tests\Feature;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProductCuttingRatioTableTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_view_and_update_shared_rates(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin']));
        $cut = Product::factory()->create(['product_type' => Product::TYPE_CUT, 'cutting_percentage' => 5]);
        $whole = Product::factory()->create(['product_type' => Product::TYPE_WHOLE]);
        $this->actingAs($admin)->get(route('products.cutting-ratios.index'))->assertOk()->assertSee($cut->name)->assertViewHas('products', fn ($products) => !$products->contains('id', $whole->id));
        $this->put(route('products.cutting-ratios.update'), ['rates' => [$cut->id => 7.5]])->assertSessionHasNoErrors();
        $this->assertSame(7.5, $cut->fresh()->cutting_percentage);
        $this->putJson(route('products.cutting-ratios.update'), ['rates' => [$cut->id => 101]])->assertUnprocessable();
        $this->assertSame(7.5, $cut->fresh()->cutting_percentage);
        $other = User::factory()->create();
        $other->roles()->attach(Role::firstOrCreate(['name' => 'sale']));
        $this->actingAs($other)->putJson(route('products.cutting-ratios.update'), ['rates' => [$cut->id => 10]])->assertForbidden();
    }
}
