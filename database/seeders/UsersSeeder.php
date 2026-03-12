<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $saleTeam = Team::where('code', 'SALE-TEAM-1')->first();
        $opsTeam = Team::where('code', 'OPS-TEAM-1')->first();

        // Danh sách role
        $roles = [
            'admin',
            'sale',
            'leader_sale',
            'manager_sale',
            'shipper',
            'leader',
            'accountant',
            'manager',
            'warehouse',
            'factory',
        ];

        // Tạo role nếu chưa có
        $roleModels = [];
        foreach ($roles as $roleName) {
            $roleModels[$roleName] = Role::firstOrCreate(['name' => $roleName]);
        }

        // Tạo user cho từng role
        $users = [
            'sale' => [
                'name' => 'Sale User',
                'email' => 'sale@example.com',
                'team_id' => $saleTeam?->id,
            ],
            'leader_sale' => [
                'name' => 'Leader User',
                'email' => 'leader@example.com',
                'team_id' => $saleTeam?->id,
            ],
            'manager_sale' => [
                'name' => 'Manager User',
                'email' => 'manager@example.com',
                'team_id' => $saleTeam?->id,
            ],
            'warehouse' => [
                'name' => 'Thanh',
                'email' => 'warehouse@example.com',
                'team_id' => $opsTeam?->id,
            ],
            'shipper' => [
                'name' => 'Ship - Dương',
                'email' => 'duong@example.com',
                'team_id' => $opsTeam?->id,
            ],
            'sale_extra' => [
                'name' => 'Giang',
                'email' => 'giang@hoanglongtnt.vn',
                'team_id' => $saleTeam?->id,
                'role' => 'sale',
            ],
            'warehouse_extra' => [
                'name' => 'Linh - Kho chiến lược',
                'email' => 'linh@example.com',
                'team_id' => $opsTeam?->id,
                'role' => 'warehouse',
            ],
            'admin' => [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'team_id' => null,
            ],
            'accountant' => [
                'name' => 'Accountant User',
                'email' => 'accountant@example.com',
                'team_id' => $saleTeam?->id,
            ],
            'factory' => [
                'name' => 'Factory User',
                'email' => 'factory@example.com',
                'team_id' => $opsTeam?->id,
            ],
        ];

        foreach ($users as $role => $userData) {
            $roleName = $userData['role'] ?? $role;
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('123456'),
                    'team_id' => $userData['team_id'] ?? null,
                ]
            );

            $user->update([
                'name' => $userData['name'],
                'team_id' => $userData['team_id'] ?? null,
            ]);

            // Gán role cho user
            $user->roles()->sync([$roleModels[$roleName]->id]);
        }
    }
}
