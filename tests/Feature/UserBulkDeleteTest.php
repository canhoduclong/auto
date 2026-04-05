<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_delete_users(): void
    {
        $adminRole = Role::create(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $usersToDelete = User::factory()->count(2)->create();
        $userToKeep = User::factory()->create();

        DB::table('customers')->insert([
            'name' => 'Assigned Customer',
            'assigned_to' => $usersToDelete->first()->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $redirectTo = route('users.index', [
            'page' => 2,
            'q' => 'assigned',
            'team_id' => 3,
        ]);

        $response = $this->actingAs($admin)->post(route('users.bulk-delete'), [
            'redirect_to' => $redirectTo,
            'user_ids' => $usersToDelete->pluck('id')->all(),
        ]);

        $response->assertRedirect($redirectTo);

        foreach ($usersToDelete as $user) {
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        }

        $this->assertDatabaseHas('customers', [
            'name' => 'Assigned Customer',
            'assigned_to' => null,
        ]);
        $this->assertDatabaseHas('users', ['id' => $userToKeep->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}