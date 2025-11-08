<?php
namespace Database\Seeders;
use App\Enums\OrderStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Customer;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $managerId = \App\Models\User::where('email', 'manager@example.com')->value('id');
        $staffId = \App\Models\User::where('email', 'staff@example.com')->value('id');
        $adminId = \App\Models\User::where('email', 'admin@example.com')->value('id');

        $ordersData = [
            [
                'id' => 1,
                'customer_id' => 1,
                'user_id' => $managerId,
                'code' => 'ORD001',
                'total' => 1500000,
                'status' => OrderStatus::Draft,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'customer_id' => 2,
                'user_id' => $staffId,
                'code' => 'ORD002',
                'total' => 2500000,
                'status' => OrderStatus::Pending,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'customer_id' => 3,
                'user_id' => $adminId,
                'code' => 'ORD003',
                'total' => 3500000,
                'status' => OrderStatus::Delivered,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($ordersData as &$orderData) {
            $customer = Customer::find($orderData['customer_id']);
            $qrCodeInfo = "Order ID: " . $orderData['code'] . "\n" .
                          "Creation Date: " . $orderData['created_at'] . "\n" .
                          "Customer Name: " . $customer->name . "\n" .
                          "Total Amount: " . $orderData['total'] . "\n" .
                          "Link: " . route('orders.show', $orderData['id']);

            $orderData['qr_code'] = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)->generate($qrCodeInfo));
        }

        DB::table('orders')->insert($ordersData);
    }
}