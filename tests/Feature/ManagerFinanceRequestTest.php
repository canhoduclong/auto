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

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'manager']));

        return $user;
    }
}
