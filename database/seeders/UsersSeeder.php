<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\Warehouse;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $snapshot = $this->loadSnapshot();
        $users = $snapshot['users'] ?? [];
        $roles = $snapshot['roles'] ?? [];

        foreach ($users as $userData) {
            foreach (($userData['roles'] ?? []) as $roleName) {
                if (!in_array($roleName, $roles, true)) {
                    $roles[] = $roleName;
                }
            }
        }

        $roleModels = [];
        foreach ($roles as $roleName) {
            if (!is_string($roleName) || $roleName === '') {
                continue;
            }

            $roleModels[$roleName] = Role::firstOrCreate(['name' => $roleName]);
        }

        $teamIdsByCode = Team::query()->pluck('id', 'code');
        $warehouseIdsByName = Warehouse::query()->pluck('id', 'name');

        foreach ($users as $userData) {
            if (!is_array($userData) || empty($userData['email'])) {
                continue;
            }

            $teamId = null;
            $warehouseId = null;

            if (!empty($userData['team_code']) && $teamIdsByCode->has($userData['team_code'])) {
                $teamId = $teamIdsByCode->get($userData['team_code']);
            }

            if (!empty($userData['warehouse_name']) && $warehouseIdsByName->has($userData['warehouse_name'])) {
                $warehouseId = $warehouseIdsByName->get($userData['warehouse_name']);
            }

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => (string) ($userData['name'] ?? $userData['email']),
                    'password' => Hash::make('h123456@'),
                    'team_id' => $teamId,
                    'warehouse_id' => $warehouseId,
                ]
            );

            $user->update([
                'name' => (string) ($userData['name'] ?? $userData['email']),
                'team_id' => $teamId,
                'warehouse_id' => $warehouseId,
            ]);

            $roleIds = collect($userData['roles'] ?? [])
                ->filter(fn ($name) => is_string($name) && isset($roleModels[$name]))
                ->map(fn ($name) => $roleModels[$name]->id)
                ->values()
                ->all();

            $user->roles()->sync($roleIds);
        }
    }

    private function loadSnapshot(): array
    {
        $snapshotPath = database_path('seeders/data/rbac_snapshot.json');

        if (!file_exists($snapshotPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($snapshotPath), true);

        return is_array($decoded) ? $decoded : [];
    }
}
