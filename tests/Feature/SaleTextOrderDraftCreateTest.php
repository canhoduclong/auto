<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TextOrderDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTextOrderDraftCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_can_create_a_private_blank_draft_from_monitoring(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $response = $this->actingAs($sale)->post(route('pages.my_order_drafts.store'));

        $draft = TextOrderDraft::query()->sole();

        $response->assertRedirect(route('pages.my_orders.monitoring', [
            'tab' => 'drafts',
            'edit' => $draft->id,
        ]));
        $response->assertSessionHas('success', 'Đã tạo đơn hàng mẫu mới.');
        $this->assertSame($sale->id, $draft->created_by);
        $this->assertSame($sale->id, $draft->sale_id);
        $this->assertSame(TextOrderDraft::SCOPE_SALE_PRIVATE, $draft->draft_scope);
        $this->assertSame('draft', $draft->status);
    }
}
