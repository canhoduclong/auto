<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ProcurementPurchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingSupplierDebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_can_view_purchase_based_supplier_debts(): void
    {
        [$accounting, $purchase] = $this->fixtures();

        $this->actingAs($accounting)
            ->get(route('accounting.supplier-debts'))
            ->assertOk()
            ->assertSee('Nhà cung cấp kiểm thử')
            ->assertSee($purchase->code)
            ->assertSee('100,000đ')
            ->assertSee('Ghi nhận trả');
    }

    public function test_accounting_can_record_a_partial_supplier_payment(): void
    {
        [$accounting, $purchase, $account] = $this->fixtures();

        $this->actingAs($accounting)
            ->post(route('accounting.supplier-debts.payments.store', $purchase), [
                'amount' => 40000,
                'account_id' => $account->id,
                'paid_at' => now()->format('Y-m-d H:i:s'),
                'note' => 'UNC-001',
            ])
            ->assertSessionHas('success');

        $purchase->refresh();
        $this->assertSame(40000.0, (float) $purchase->paid_amount);
        $this->assertSame(60000.0, (float) $purchase->remaining_amount);
        $this->assertSame('partial', $purchase->payment_status);
        $this->assertDatabaseHas('supplier_debt_payments', [
            'procurement_purchase_id' => $purchase->id,
            'amount' => 40000,
        ]);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'amount' => 40000,
            'status' => Transaction::STATUS_APPROVED,
        ]);
        $this->assertSame(960000.0, (float) $account->fresh()->balance);
    }

    public function test_completing_procurement_request_updates_supplier_debt(): void
    {
        [$accounting, $purchase, $account, $category] = $this->fixtures();
        $transaction = Transaction::create([
            'amount' => 100000,
            'type' => 'extra_expense',
            'transaction_category_id' => $category->id,
            'account_id' => $account->id,
            'status' => Transaction::STATUS_APPROVED_PENDING_COMPLETION,
            'request_source' => 'procurement',
            'submitted_by' => $accounting->id,
        ]);
        $purchase->update(['payment_transaction_id' => $transaction->id]);

        $this->actingAs($accounting)
            ->post(route('accounting.transactions.complete', $transaction), ['note' => 'Đã chuyển khoản'])
            ->assertSessionHas('success');

        $purchase->refresh();
        $this->assertSame(100000.0, (float) $purchase->paid_amount);
        $this->assertSame(0.0, (float) $purchase->remaining_amount);
        $this->assertSame('paid', $purchase->payment_status);
        $this->assertDatabaseHas('supplier_debt_payments', [
            'procurement_purchase_id' => $purchase->id,
            'transaction_id' => $transaction->id,
            'amount' => 100000,
        ]);
    }

    private function fixtures(): array
    {
        $accounting = User::factory()->create();
        $accounting->roles()->attach(Role::create(['name' => 'accounting']));
        $supplier = Supplier::create([
            'name' => 'Nhà cung cấp kiểm thử',
            'phone' => '0900000000',
            'is_active' => true,
        ]);
        $account = Account::create([
            'name' => 'Tài khoản kiểm thử',
            'type' => 'bank',
            'opening_balance' => 1000000,
            'balance' => 1000000,
            'is_active' => true,
        ]);
        $category = TransactionCategory::create([
            'code' => 'PROCUREMENT-TEST',
            'name' => 'Chi phí thu mua kiểm thử',
            'flow_direction' => 'out',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $purchase = ProcurementPurchase::create([
            'code' => 'TM-TEST-001',
            'purchase_type' => 'processed_duck',
            'supplier_id' => $supplier->id,
            'created_by' => $accounting->id,
            'quantity' => 10,
            'total_weight' => 20,
            'average_weight' => 2,
            'unit_price' => 5000,
            'subtotal' => 100000,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'payment_status' => 'unpaid',
            'purchased_at' => now(),
            'status' => ProcurementPurchase::STATUS_DRAFT,
        ]);

        return [$accounting, $purchase, $account, $category];
    }
}
