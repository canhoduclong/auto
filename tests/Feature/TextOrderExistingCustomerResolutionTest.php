<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TextOrderImportController;
use App\Models\Customer;
use App\Models\Role;
use App\Models\TextOrderDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class TextOrderExistingCustomerResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_order_reuses_customer_with_the_same_normalized_name(): void
    {
        $sale = User::factory()->create(['name' => 'Sale nhập đơn']);
        $sale->roles()->attach(Role::create(['name' => 'sale']));
        $existing = Customer::create([
            'name' => 'Duy Khánh',
            'assigned_to' => $sale->id,
            'current_owner_sale_id' => $sale->id,
            'current_cycle_no' => 1,
            'customer_status' => 'active',
            'status' => 'active',
        ]);
        $draft = TextOrderDraft::create([
            'created_by' => $sale->id,
            'draft_scope' => TextOrderDraft::SCOPE_ADMIN_IMPORT,
            'sale_id' => $sale->id,
            'customer_name' => '  Duy   Khánh ',
            'phone' => '09398825544',
            'address' => '111 Vành Đai Trong',
            'raw_text' => 'Dữ liệu kiểm thử',
        ]);

        $method = new ReflectionMethod(TextOrderImportController::class, 'resolveOrCreateCustomer');
        $resolved = $method->invoke(app(TextOrderImportController::class), $draft, null);

        $this->assertSame($existing->id, $resolved->id);
        $this->assertSame(1, Customer::withTrashed()->count());
        $this->assertDatabaseHas('text_order_drafts', [
            'id' => $draft->id,
            'customer_id' => $existing->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $existing->id,
            'phone' => '09398825544',
            'address' => '111 Vành Đai Trong',
        ]);
    }
}
