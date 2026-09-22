<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManagerFinanceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_index_shows_list_and_create_button_without_creation_form(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('manager.finance-requests.index'))
            ->assertOk()
            ->assertSee('Phiếu đã gửi')
            ->assertSee('Tạo mới')
            ->assertSee(route('manager.finance-requests.create'), false)
            ->assertDontSee('name="request_title"', false);

        $this->get(route('manager.finance-requests.create'))
            ->assertOk()
            ->assertSee('Tạo phiếu tài chính')
            ->assertSee('name="request_title"', false)
            ->assertSee('name="attachments[]"', false)
            ->assertDontSee('Phiếu đã gửi');
    }

    public function test_manager_can_create_request_with_multiple_attachments(): void
    {
        Storage::fake('public');
        $manager = $this->manager();

        $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('manager.finance-requests.store'), [
                'request_form_type' => Transaction::REQUEST_FORM_CASH,
                'flow_direction' => 'out',
                'request_title' => 'Thanh toán vật tư',
                'items' => [[
                    'content' => 'Mua vật tư',
                    'unit' => 'lần',
                    'quantity' => 1,
                    'unit_price' => 500000,
                ]],
                'request_vat' => 0,
                'method' => 'cash',
                'note' => 'Chứng từ kiểm thử',
                'attachments' => [
                    UploadedFile::fake()->image('hoa-don.jpg'),
                    UploadedFile::fake()->create('bao-gia.pdf', 200, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('manager.finance-requests.index'))
            ->assertSessionHasNoErrors();

        $transaction = Transaction::query()->where('request_source', 'manager')->firstOrFail();
        $this->assertCount(2, $transaction->request_attachments);
        $this->assertSame('hoa-don.jpg', $transaction->request_attachments[0]['name']);
        $this->assertSame('bao-gia.pdf', $transaction->request_attachments[1]['name']);
        foreach ($transaction->request_attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment['path']);
        }
    }

    public function test_manager_can_duplicate_request_into_a_fresh_approval_flow(): void
    {
        $manager = $this->manager();
        $original = Transaction::query()->create([
            'amount' => 500000,
            'type' => 'extra_expense',
            'method' => 'cash',
            'note' => 'Chi phí cần lặp lại',
            'status' => Transaction::STATUS_APPROVED,
            'submitted_by' => $manager->id,
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'request_source' => 'manager',
            'request_department' => 'Bộ phận cũ',
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'request_title' => 'Phiếu định kỳ',
            'request_items' => [['stt' => 1, 'content' => 'Chi phí', 'quantity' => 1, 'unit_price' => 500000, 'line_total' => 500000]],
            'request_subtotal' => 500000,
            'request_vat' => 0,
            'request_total' => 500000,
        ]);

        $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->post(route('manager.finance-requests.duplicate', $original))
            ->assertRedirect(route('manager.finance-requests.index'))
            ->assertSessionHas('success');

        $copy = Transaction::query()->whereKeyNot($original->id)->firstOrFail();
        $this->assertSame(Transaction::STATUS_PENDING_APPROVAL, $copy->status);
        $this->assertSame($manager->id, $copy->submitted_by);
        $this->assertSame('manager', $copy->request_source);
        $this->assertSame('Manager', $copy->request_department);
        $this->assertSame('Phiếu định kỳ', $copy->request_title);
        $this->assertNull($copy->approved_by);
        $this->assertNull($copy->approved_at);
    }

    public function test_manager_can_search_edit_and_delete_manageable_requests(): void
    {
        $manager = $this->manager();
        $request = Transaction::query()->create([
            'amount' => 100000,
            'type' => 'extra_expense',
            'method' => 'cash',
            'note' => 'Mua vật tư cũ',
            'status' => Transaction::STATUS_PENDING_APPROVAL,
            'submitted_by' => $manager->id,
            'request_source' => 'manager',
            'request_department' => 'Manager',
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'request_title' => 'Phiếu cần quản trị',
            'request_items' => [['content' => 'Vật tư', 'quantity' => 1, 'unit_price' => 100000, 'line_total' => 100000]],
            'request_subtotal' => 100000,
            'request_vat' => 0,
            'request_total' => 100000,
        ]);

        $this->actingAs($manager)
            ->withSession(['active_role' => 'manager'])
            ->get(route('manager.finance-requests.index', ['search' => 'cần quản trị']))
            ->assertOk()
            ->assertSee('Phiếu cần quản trị')
            ->assertSee(route('manager.finance-requests.edit', $request), false);

        $this->get(route('manager.finance-requests.edit', $request))
            ->assertOk()->assertSee('Sửa phiếu #'.$request->id);

        $this->put(route('manager.finance-requests.update', $request), [
            'request_form_type' => Transaction::REQUEST_FORM_CASH,
            'flow_direction' => 'out',
            'request_title' => 'Phiếu đã sửa đúng',
            'items' => [['content' => 'Vật tư đúng', 'unit' => 'lần', 'quantity' => 2, 'unit_price' => 75000]],
            'request_vat' => 10000,
            'method' => 'cash',
            'note' => 'Nội dung đã sửa',
        ])->assertRedirect(route('manager.finance-requests.index'))->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame('Phiếu đã sửa đúng', $request->request_title);
        $this->assertSame('160000.00', $request->amount);
        $this->assertSame(Transaction::STATUS_PENDING_APPROVAL, $request->status);

        $this->delete(route('manager.finance-requests.destroy', $request))
            ->assertRedirect(route('manager.finance-requests.index'));
        $this->assertDatabaseMissing('transactions', ['id' => $request->id]);
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'manager']));

        return $user;
    }
}
