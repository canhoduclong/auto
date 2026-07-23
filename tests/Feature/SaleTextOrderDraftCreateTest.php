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

    public function test_sale_can_save_note_and_truck_station_details_on_draft(): void
    {
        $saleRole = Role::query()->create(['name' => 'sale']);
        $sale = User::factory()->create();
        $sale->roles()->attach($saleRole);

        $draft = TextOrderDraft::query()->create([
            'created_by' => $sale->id,
            'draft_scope' => TextOrderDraft::SCOPE_SALE_PRIVATE,
            'sale_id' => $sale->id,
            'raw_text' => '',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($sale)->put(route('pages.my_order_drafts.update', $draft), [
            'sale_id' => $sale->id,
            'use_truck_station' => true,
            'truck_brand_name' => 'Nhà xe Hoàng Long',
            'truck_station_name' => 'Trạm Miền Đông',
            'truck_station_address' => '292 Đinh Bộ Lĩnh',
            'truck_station_phone' => '0909000000',
            'truck_receive_time' => 'Trước 17h',
            'note' => 'Đóng thùng kỹ và gọi khách trước khi gửi.',
        ]);

        $response->assertOk()->assertJsonPath('message', 'Đã lưu thay đổi đơn mẫu.');

        $draft->refresh();
        $this->assertTrue($draft->use_truck_station);
        $this->assertSame('Nhà xe Hoàng Long', $draft->truck_brand_name);
        $this->assertSame('Trạm Miền Đông', $draft->truck_station_name);
        $this->assertSame('292 Đinh Bộ Lĩnh', $draft->truck_station_address);
        $this->assertSame('0909000000', $draft->truck_station_phone);
        $this->assertSame('Trước 17h', $draft->truck_receive_time);
        $this->assertSame('Đóng thùng kỹ và gọi khách trước khi gửi.', $draft->note);

        $page = $this->actingAs($sale)->get(route('pages.my_orders.monitoring', ['tab' => 'drafts']));
        $page->assertOk()
            ->assertSee('Ghi chú đơn hàng')
            ->assertSee('Gửi hàng qua nhà xe')
            ->assertSee('Trạm Miền Đông')
            ->assertSee('Đóng thùng kỹ và gọi khách trước khi gửi.');
    }
}
