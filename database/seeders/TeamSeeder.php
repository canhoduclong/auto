<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        Team::updateOrCreate(
            ['code' => 'SALE-TEAM-1'],
            [
                'name' => 'Sale Team 1',
                'note' => 'Team van hanh sale/leader/manager',
            ]
        );

        Team::updateOrCreate(
            ['code' => 'OPS-TEAM-1'],
            [
                'name' => 'Operations Team 1',
                'note' => 'Team kho va giao nhan',
            ]
        );
    }
}
