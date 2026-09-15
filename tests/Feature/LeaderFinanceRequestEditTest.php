<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderFinanceRequestEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_edit_own_pending_request(): void
    {
        $leader = $this->leader();
        $transaction = $this->requestFor($leader);

        $this->actingAs($leader)
            ->get(route('leader.finance-requests.edit', $transaction))
            ->assertOk()
            ->assertSee('Sửa phiếu #' . $transaction->id)
            ->assertSee('Phiếu ban đầu');

        $this->put(route('leader.finance-requests.update', $transaction), [
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'flow_direction' => 'in',
            'request_title' => 'Phiếu đã sửa',
            'items' => [
                ['content' => 'Thu hoàn ứng', 'unit' => 'lần', 'quantity' => 2, 'unit_price' => 750000],
            ],
            'request_vat' => 150000,
            'method' => 'cash',
            'note' => 'Nội dung sau khi sửa',
        ])->assertRedirect(route('leader.finance-requests.index'))
            ->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame('Phiếu đã sửa', $transaction->request_title);
        $this->assertSame('extra_income', $transaction->type);
        $this->assertSame('1650000.00', $transaction->request_total);
        $this->assertSame($leader->id, $transaction->submitted_by);
        $this->assertSame('Thu hoàn ứng', $transaction->request_items[0]['content']);
    }

    public function test_leader_cannot_edit_another_leaders_request(): void
    {
        $owner = $this->leader();
        $otherLeader = $this->leader();

        $this->actingAs($otherLeader)
            ->putJson(route('leader.finance-requests.update', $this->requestFor($owner)), $this->validPayload())
            ->assertForbidden();
    }

    public function test_approved_request_cannot_be_edited(): void
    {
        $leader = $this->leader();
        $transaction = $this->requestFor($leader, Transaction::STATUS_APPROVED);

        $this->actingAs($leader)
            ->putJson(route('leader.finance-requests.update', $transaction), $this->validPayload())
            ->assertForbidden();
    }

    public function test_rejected_request_can_be_edited_and_resubmitted(): void
    {
        $leader = $this->leader();
        $transaction = $this->requestFor($leader, Transaction::STATUS_REJECTED);
        $transaction->update([
            'rejected_by' => $leader->id,
            'rejected_at' => now(),
            'reject_reason' => 'Thiếu nội dung',
        ]);

        $this->actingAs($leader)
            ->get(route('leader.finance-requests.edit', $transaction))
            ->assertOk()
            ->assertSee('Sửa và gửi lại phiếu #' . $transaction->id)
            ->assertSee('Lưu và gửi lại');

        $this->put(route('leader.finance-requests.update', $transaction), $this->validPayload())
            ->assertRedirect(route('leader.finance-requests.index'))
            ->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame(Transaction::STATUS_PENDING_APPROVAL, $transaction->status);
        $this->assertSame('Không được cập nhật', $transaction->request_title);
        $this->assertNull($transaction->rejected_by);
        $this->assertNull($transaction->rejected_at);
        $this->assertNull($transaction->reject_reason);
    }

    private function leader(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'leader']));

        return $user;
    }

    private function requestFor(User $user, string $status = Transaction::STATUS_PENDING_APPROVAL): Transaction
    {
        return Transaction::query()->create([
            'amount' => 1000000,
            'type' => 'extra_expense',
            'method' => 'cash',
            'note' => 'Nội dung ban đầu',
            'status' => $status,
            'submitted_by' => $user->id,
            'request_source' => 'leader',
            'request_department' => 'Leader',
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'request_title' => 'Phiếu ban đầu',
            'request_items' => [['stt' => 1, 'content' => 'Chi phí', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => 1000000, 'line_total' => 1000000]],
            'request_subtotal' => 1000000,
            'request_vat' => 0,
            'request_total' => 1000000,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'flow_direction' => 'out',
            'request_title' => 'Không được cập nhật',
            'items' => [['content' => 'Chi phí', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => 1000000]],
            'request_vat' => 0,
            'method' => 'cash',
            'note' => 'Không được cập nhật',
        ];
    }
}
