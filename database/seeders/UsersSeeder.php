<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Danh sách role
        $roles = [
            'admin',
            'sale',
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
            'admin' => [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ],
            'sale' => [
                'name' => 'Sale User',
                'email' => 'sale@example.com',
            ],
            'leader' => [
                'name' => 'Leader User',
                'email' => 'leader@example.com',
            ],
            'accountant' => [
                'name' => 'Accountant User',
                'email' => 'accountant@example.com',
            ],
            'manager' => [
                'name' => 'Manager User',
                'email' => 'manager@example.com',
            ],
            'warehouse' => [
                'name' => 'Warehouse User',
                'email' => 'warehouse@example.com',
            ],
            'factory' => [
                'name' => 'Factory User',
                'email' => 'factory@example.com',
            ],
        ];

        foreach ($users as $role => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('123456'),
                ]
            );

            // Gán role cho user
            $user->roles()->sync([$roleModels[$role]->id]);
        }
    }
}
