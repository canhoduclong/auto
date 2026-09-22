<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinanceRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_and_correct_request_source_department_and_details(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->firstOrCreate(['name' => 'admin']));
        $submitter = User::factory()->create(['name' => 'Người dùng nhiều vai trò']);
        $submitter->roles()->attach([
            Role::query()->firstOrCreate(['name' => 'leader'])->id,
            Role::query()->firstOrCreate(['name' => 'manager'])->id,
        ]);
        $transaction = Transaction::query()->create([
            'amount' => 100000,
            'type' => 'extra_expense',
            'method' => 'cash',
            'note' => 'Nội dung cũ',
            'status' => Transaction::STATUS_PENDING_APPROVAL,
            'submitted_by' => $submitter->id,
            'request_source' => 'leader',
            'request_department' => 'Leader',
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'request_title' => 'Phiếu cần sửa',
            'request_items' => [['content' => 'Chi cũ', 'quantity' => 1, 'unit_price' => 100000, 'line_total' => 100000]],
            'request_subtotal' => 100000,
            'request_vat' => 0,
            'request_total' => 100000,
        ]);

        $this->actingAs($admin)->get(route('admin.accounting.finance-requests.index'))
            ->assertOk()->assertSee('Quản trị phiếu yêu cầu')->assertSee('Phiếu cần sửa');
        $this->get(route('admin.accounting.finance-requests.edit', $transaction))
            ->assertOk()->assertSee('Người dùng nhiều vai trò')->assertSee('✓ vai trò của người dùng');

        $this->put(route('admin.accounting.finance-requests.update', $transaction), [
            'request_source' => 'manager',
            'request_department' => 'Phòng Kinh doanh Miền Nam',
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'flow_direction' => 'out',
            'request_title' => 'Phiếu đã hiệu chỉnh',
            'items' => [['content' => 'Chi đúng', 'unit' => 'lần', 'quantity' => 2, 'unit_price' => 75000]],
            'request_vat' => 15000,
            'method' => 'cash',
            'note' => 'Nội dung đúng',
        ])->assertRedirect(route('admin.accounting.finance-requests.index'))->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertSame('manager', $transaction->request_source);
        $this->assertSame('Phòng Kinh doanh Miền Nam', $transaction->request_department);
        $this->assertSame('Phiếu đã hiệu chỉnh', $transaction->request_title);
        $this->assertSame('165000.00', $transaction->amount);
        $this->assertSame(Transaction::STATUS_PENDING_APPROVAL, $transaction->status);
        $this->assertSame($submitter->id, $transaction->submitted_by);
    }

    public function test_non_admin_cannot_access_request_management(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'leader']));

        $this->actingAs($user)->get(route('admin.accounting.finance-requests.index'))->assertRedirect(route('home'));
    }
}
