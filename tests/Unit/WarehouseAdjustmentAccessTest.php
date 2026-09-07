<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\WarehouseAdjustmentAccess;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WarehouseAdjustmentAccessTest extends TestCase
{
    #[DataProvider('roles')]
    public function test_only_authorized_orders_are_visible(string $role, ?int $teamId, array $expected): void
    {
        config(['database.connections.adjustment_access_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        $originalConnection = config('database.default');
        config(['database.default' => 'adjustment_access_test']);
        $db = DB::connection('adjustment_access_test');
        try {
            $db->statement('CREATE TABLE users (id INTEGER, team_id INTEGER)');
            $db->statement('CREATE TABLE orders (id INTEGER, user_id INTEGER)');
            $db->table('users')->insert([
                ['id' => 1, 'team_id' => 10], ['id' => 2, 'team_id' => 10], ['id' => 3, 'team_id' => 20],
            ]);
            $db->table('orders')->insert([
                ['id' => 11, 'user_id' => 1], ['id' => 12, 'user_id' => 2], ['id' => 13, 'user_id' => 3],
            ]);
            $user = new User;
            $user->forceFill(['id' => 1, 'team_id' => $teamId]);
            $user->setRelation('roles', collect([new Role(['name' => $role])]));
            $actual = app(WarehouseAdjustmentAccess::class)
                ->scope(Order::on('adjustment_access_test'), $user)->orderBy('id')->pluck('id')->all();
            $this->assertSame($expected, $actual);
        } finally {
            config(['database.default' => $originalConnection]);
            DB::purge('adjustment_access_test');
        }
    }

    public static function roles(): array
    {
        return [
            ['sale', 10, [11]],
            ['leader', 10, [11, 12]],
            ['leader_sale', 10, [11, 12]],
            ['sale_manager', 10, [11, 12]],
            ['leader', null, [11]],
            ['manager', 10, [11, 12, 13]],
            ['manager_sale', 10, [11, 12, 13]],
            ['admin', null, [11, 12, 13]],
            ['warehouse', 10, []],
            ['package', 10, []],
            ['shipper', 10, []],
            ['accountant', 10, []],
        ];
    }
}
