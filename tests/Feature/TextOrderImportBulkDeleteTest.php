<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TextOrderDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextOrderImportBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_delete_only_admin_import_drafts(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $draftsToDelete = collect([
            TextOrderDraft::query()->create([
                'created_by' => $admin->id,
                'draft_scope' => TextOrderDraft::SCOPE_ADMIN_IMPORT,
                'raw_text' => 'draft 1',
            ]),
            TextOrderDraft::query()->create([
                'created_by' => $admin->id,
                'draft_scope' => TextOrderDraft::SCOPE_ADMIN_IMPORT,
                'raw_text' => 'draft 2',
            ]),
        ]);
        $privateDraft = TextOrderDraft::query()->create([
            'created_by' => $admin->id,
            'sale_id' => $admin->id,
            'draft_scope' => TextOrderDraft::SCOPE_SALE_PRIVATE,
            'raw_text' => 'private draft',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.text-order-import.bulk-delete'), [
            'draft_ids' => $draftsToDelete->pluck('id')->all(),
        ]);

        $response->assertOk()->assertJsonPath('message', 'Đã xóa 2 dòng import. Đơn sale đã tạo (nếu có) không bị ảnh hưởng.');
        foreach ($draftsToDelete as $draft) {
            $this->assertDatabaseMissing('text_order_drafts', ['id' => $draft->id]);
        }
        $this->assertDatabaseHas('text_order_drafts', ['id' => $privateDraft->id]);
    }

    public function test_bulk_delete_rejects_sale_private_drafts(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        $privateDraft = TextOrderDraft::query()->create([
            'created_by' => $admin->id,
            'sale_id' => $admin->id,
            'draft_scope' => TextOrderDraft::SCOPE_SALE_PRIVATE,
            'raw_text' => 'private draft',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.text-order-import.bulk-delete'), [
            'draft_ids' => [$privateDraft->id],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('draft_ids.0');
        $this->assertDatabaseHas('text_order_drafts', ['id' => $privateDraft->id]);
    }
}
