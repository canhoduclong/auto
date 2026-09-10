<?php

namespace Tests\Feature;

use App\Models\{Role, User, ShipperDispatchHistory};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DispatchHistoryDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_uses_the_selected_days_history_version(): void
    {
        $manager = User::factory()->create();
        $manager->roles()->attach(Role::create(['name' => 'manager_shipper']));
        $history = ShipperDispatchHistory::create([
            'schedule_date' => '2026-09-09', 'version' => 1, 'created_by' => $manager->id,
            'published_at' => now(), 'route_plan' => [['shipper_name' => 'Ship A', 'routes' => [
                ['name' => 'Chuyến 1', 'orders' => [['order_id' => 10, 'customer_name' => 'Khách bản cũ', 'final_fee' => 10000]]],
            ]]],
        ]);
        ShipperDispatchHistory::create([
            'schedule_date' => '2026-09-09', 'version' => 2, 'created_by' => $manager->id,
            'published_at' => now(), 'route_plan' => [],
        ]);
        Excel::fake();
        $this->actingAs($manager)->get(route('shipper.manage-assignments.history', [
            'date' => '2026-09-09', 'history_id' => $history->id, 'download' => 'excel',
        ]))->assertOk();
        Excel::assertDownloaded('dieu-phoi-tong-2026-09-09-lan-1.xlsx', function ($export) {
            return $export->array()[0][7] === 'Khách bản cũ' && count($export->array()) === 2;
        });
    }
}
