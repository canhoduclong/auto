<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_delete_categories_and_keep_current_pagination_url(): void
    {
        $adminRole = Role::create(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $categoriesToDelete = Category::factory()->count(2)->create();
        $categoryToKeep = Category::factory()->create();

        $redirectTo = route('categories.index', [
            'page' => 2,
            'q' => 'danh-muc',
        ]);

        $response = $this->actingAs($admin)->post(route('categories.bulk-delete'), [
            'redirect_to' => $redirectTo,
            'category_ids' => $categoriesToDelete->pluck('id')->all(),
        ]);

        $response->assertRedirect($redirectTo);

        foreach ($categoriesToDelete as $category) {
            $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        }

        $this->assertDatabaseHas('categories', ['id' => $categoryToKeep->id]);
    }
}
