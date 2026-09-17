<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepartmentFinanceRequestMenuAndTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_and_accounting_layouts_expose_their_own_finance_request_menu(): void
    {
        foreach ([
            ['role' => 'package', 'route' => 'package.finance-requests.index', 'label' => 'Phiếu yêu cầu'],
            ['role' => 'accounting', 'route' => 'accounting.finance-requests.index', 'label' => 'Phiếu yêu cầu của tôi'],
        ] as $case) {
            $user = User::factory()->create();
            $user->roles()->attach(Role::query()->firstOrCreate(['name' => $case['role']]));

            $this->actingAs($user)
                ->withSession(['active_role' => $case['role']])
                ->get(route($case['route']))
                ->assertOk()
                ->assertSee($case['label']);
        }
    }

    public function test_created_time_is_displayed_in_vietnam_timezone(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'leader']));
        $transaction = Transaction::query()->create([
            'amount' => 600000,
            'type' => 'extra_expense',
            'method' => 'cash',
            'note' => 'Kiểm tra thời gian',
            'status' => Transaction::STATUS_PENDING_APPROVAL,
            'submitted_by' => $user->id,
            'request_source' => 'leader',
            'request_department' => 'Leader',
            'request_form_type' => Transaction::REQUEST_FORM_PAYMENT,
            'request_title' => 'Phiếu kiểm tra múi giờ',
            'request_items' => [],
            'request_subtotal' => 600000,
            'request_vat' => 0,
            'request_total' => 600000,
        ]);
        $transaction->forceFill(['created_at' => Carbon::parse('2026-09-17 08:51:00', 'UTC')])->saveQuietly();

        $this->actingAs($user)
            ->withSession(['active_role' => 'leader'])
            ->get(route('leader.finance-requests.index'))
            ->assertOk()
            ->assertSee('17/09/2026 15:51');
    }

    public function test_accounting_uses_request_detail_total_and_cannot_edit_it_independently(): void
    {
        $accounting = User::factory()->create();
        $accounting->roles()->attach(Role::query()->firstOrCreate(['name' => 'accounting']));
        $leader = User::factory()->create();
        $transaction = Transaction::query()->create([
            'amount' => 74000000,
            'type' => 'extra_expense',
            'method' => 'bank_transfer',
            'note' => 'Chi phí ship hàng',
            'status' => Transaction::STATUS_PENDING_APPROVAL,
            'submitted_by' => $leader->id,
            'request_source' => 'leader',
            'request_department' => 'Leader',
            'request_form_type' => Transaction::REQUEST_FORM_PAYMENT,
            'request_title' => 'Chi phí ship hàng',
            'request_items' => [[
                'content' => 'Ship hàng', 'unit' => '', 'quantity' => 1,
                'unit_price' => 740000, 'line_total' => 740000,
            ]],
            'request_subtotal' => 740000,
            'request_vat' => 0,
            'request_total' => 740000,
        ]);

        $this->actingAs($accounting)
            ->withSession(['active_role' => 'accounting'])
            ->get(route('accounting.cashflow.show', $transaction))
            ->assertOk()
            ->assertSee('740.000đ')
            ->assertDontSee('74.000.000đ')
            ->assertDontSee('Sửa giao dịch');

        $this->assertSame('740000.00', $transaction->fresh()->amount);
        $this->withSession(['active_role' => 'accounting'])
            ->getJson(route('accounting.transactions.edit', $transaction))
            ->assertForbidden();
    }

    public function test_accounting_must_upload_transfer_proof_when_director_has_not_uploaded_one(): void
    {
        Storage::fake('public');
        $accounting = User::factory()->create();
        $accounting->roles()->attach(Role::query()->firstOrCreate(['name' => 'accounting']));
        $category = TransactionCategory::query()->create([
            'code' => 'CPK', 'name' => 'Chi phí khác', 'flow_direction' => 'out', 'is_active' => true,
        ]);
        $account = Account::query()->create([
            'name' => 'Tài khoản kiểm thử', 'type' => 'bank', 'balance' => 10000000, 'is_active' => true,
        ]);
        $transaction = Transaction::query()->create([
            'amount' => 740000,
            'type' => 'extra_expense',
            'method' => 'bank_transfer',
            'status' => Transaction::STATUS_APPROVED_PENDING_COMPLETION,
            'submitted_by' => $accounting->id,
            'request_source' => 'leader',
            'request_department' => 'Leader',
            'request_form_type' => Transaction::REQUEST_FORM_PAYMENT,
            'request_title' => 'Chi phí ship hàng',
            'request_items' => [['content' => 'Ship hàng', 'quantity' => 1, 'unit_price' => 740000, 'line_total' => 740000]],
            'request_subtotal' => 740000,
            'request_vat' => 0,
            'request_total' => 740000,
            'transaction_category_id' => $category->id,
            'account_id' => $account->id,
        ]);

        $this->actingAs($accounting)
            ->withSession(['active_role' => 'accounting'])
            ->post(route('accounting.transactions.complete', $transaction))
            ->assertSessionHasErrors('transfer_proof');
        $this->assertSame(Transaction::STATUS_APPROVED_PENDING_COMPLETION, $transaction->fresh()->status);

        $this->withSession(['active_role' => 'accounting'])
            ->post(route('accounting.transactions.complete', $transaction), [
                'transfer_proof' => UploadedFile::fake()->image('chung-tu.png'),
            ])
            ->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertSame(Transaction::STATUS_APPROVED, $transaction->status);
        $this->assertSame($accounting->id, $transaction->transfer_proof_uploaded_by);
        $this->assertNotNull($transaction->transfer_proof_uploaded_at);
        Storage::disk('public')->assertExists($transaction->transfer_proof_path);
    }

    public function test_accounting_can_view_director_proof_but_cannot_upload_it_again(): void
    {
        $accounting = User::factory()->create();
        $accounting->roles()->attach(Role::query()->firstOrCreate(['name' => 'accounting']));
        $director = User::factory()->create(['name' => 'Director kiểm thử']);
        $transaction = Transaction::query()->create([
            'amount' => 100000,
            'type' => 'extra_expense',
            'method' => 'bank_transfer',
            'status' => Transaction::STATUS_APPROVED_PENDING_COMPLETION,
            'submitted_by' => $accounting->id,
            'request_source' => 'leader',
            'request_department' => 'Leader',
            'request_form_type' => Transaction::REQUEST_FORM_PAYMENT,
            'request_title' => 'Phiếu đã có chứng từ',
            'request_items' => [],
            'request_subtotal' => 100000,
            'request_vat' => 0,
            'request_total' => 100000,
            'transfer_proof_path' => 'transactions/transfer-proofs/director-proof.pdf',
            'transfer_proof_uploaded_by' => $director->id,
            'transfer_proof_uploaded_at' => now(),
        ]);

        $this->actingAs($accounting)
            ->withSession(['active_role' => 'accounting'])
            ->get(route('accounting.cashflow.show', $transaction))
            ->assertOk()
            ->assertSee('Director kiểm thử')
            ->assertSee('Director đã tải chứng từ')
            ->assertDontSee('name="transfer_proof"', false);
    }
}
