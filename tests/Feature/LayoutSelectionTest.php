<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LayoutSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_to_layout_selection_when_user_has_multiple_workspaces_without_default(): void
    {
        $saleRole = Role::create(['name' => 'sale']);
        $warehouseRole = Role::create(['name' => 'warehouse']);

        $user = User::factory()->create([
            'email' => 'multi@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $user->roles()->attach([$saleRole->id, $warehouseRole->id]);

        $response = $this->post('/login', [
            'email' => 'multi@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('layout-selection.show'));
    }

    public function test_posting_layout_selection_can_persist_default_workspace(): void
    {
        $saleRole = Role::create(['name' => 'sale']);

        $user = User::factory()->create();
        $user->roles()->attach($saleRole);

        $response = $this->actingAs($user)->post(route('layout-selection.store'), [
            'workspace' => 'my-app-sales',
            'remember_default' => '1',
        ]);

        $response->assertRedirect(route('mobile.sale.home'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'default_workspace' => 'my-app-sales',
        ]);
    }
}