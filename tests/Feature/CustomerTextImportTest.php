<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerTextImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTextImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_tabular_text_without_shifting_rows_that_have_an_empty_code(): void
    {
        $actor = User::factory()->create();
        $text = "**Mã KH**\t**Khách Hàng**\t**NVKD**\t**SĐT**\t**Địa Chỉ**\n"
            ."8999\tLan Hà\tHuy\t72.768.999\t\n"
            ."\tCÔNG TY TNHH KITCHEN\tHuy\t319260161\t";

        $result = app(CustomerTextImportService::class)->preview($text, $actor);

        $this->assertCount(2, $result['rows']);
        $this->assertSame('8999', $result['rows'][0]['data']['customer_code']);
        $this->assertSame('072768999', $result['rows'][0]['data']['phone']);
        $this->assertNull($result['rows'][1]['data']['customer_code']);
        $this->assertSame('CÔNG TY TNHH KITCHEN', $result['rows'][1]['data']['name']);
        $this->assertSame('0319260161', $result['rows'][1]['data']['phone']);
    }

    public function test_existing_customer_is_previewed_as_supplement_or_skip_without_overwriting(): void
    {
        $actor = User::factory()->create();
        Customer::create([
            'name' => 'Lan Hà',
            'phone' => '0900000000',
            'customer_code' => null,
            'assigned_to' => $actor->id,
            'current_owner_sale_id' => $actor->id,
        ]);

        $service = app(CustomerTextImportService::class);
        $supplement = $service->preview("Mã KH\tKhách Hàng\tNVKD\tSĐT\tĐịa Chỉ\n8999\tLan Hà\tHuy\t0123456789\t", $actor);

        $this->assertSame('update', $supplement['rows'][0]['action']);

        $unchanged = $service->preview("Mã KH\tKhách Hàng\tNVKD\tSĐT\tĐịa Chỉ\n\tLan Hà\tHuy\t0123456789\t", $actor);
        $this->assertSame('skip', $unchanged['rows'][0]['action']);
    }

    public function test_admin_can_import_new_customer_with_an_optional_address(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'admin']));
        $text = "Mã KH\tKhách Hàng\tNVKD\tSĐT\tĐịa Chỉ\n"
            ."8819\tHữu Thông\t\t327288819\t1360 TL43, Bình Hoà";

        $result = app(CustomerTextImportService::class)->import($text, $admin);

        $this->assertTrue($result['imported']);
        $this->assertDatabaseHas('customers', [
            'customer_code' => '8819',
            'name' => 'Hữu Thông',
            'phone' => '0327288819',
            'assigned_to' => null,
        ]);
        $customer = Customer::where('customer_code', '8819')->firstOrFail();
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'note' => '1360 TL43, Bình Hoà',
            'is_default' => 1,
        ]);
    }

    public function test_admin_can_map_an_imported_short_sale_name_to_a_system_user(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $saleRole = Role::create(['name' => 'sale']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $sale = User::factory()->create(['name' => 'Nguyễn Văn Huy', 'short_name' => 'Huy mới']);
        $sale->roles()->attach($saleRole);
        $text = "Mã KH\tKhách Hàng\tNVKD\tSĐT\tĐịa Chỉ\n10001\tKhách ánh xạ\tHuy cũ\t0912345678\t";
        $service = app(CustomerTextImportService::class);

        $unmapped = $service->preview($text, $admin);
        $this->assertSame('error', $unmapped['rows'][0]['action']);
        $mappingKey = $unmapped['sale_mappings'][0]['key'];

        $mapped = $service->import($text, $admin, [$mappingKey => $sale->id]);

        $this->assertTrue($mapped['imported']);
        $this->assertDatabaseHas('customers', [
            'customer_code' => '10001',
            'assigned_to' => $sale->id,
            'current_owner_sale_id' => $sale->id,
        ]);
    }
}
