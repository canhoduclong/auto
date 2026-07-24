<?php

namespace Tests\Unit;

use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeamRoutesTest extends TestCase
{
    public function test_team_member_management_routes_are_registered(): void
    {
        $assignRoute = Route::getRoutes()->getByName('teams.assign-user');
        $removeRoute = Route::getRoutes()->getByName('teams.remove-user');

        $this->assertNotNull($assignRoute);
        $this->assertSame(['POST'], $assignRoute->methods());
        $this->assertSame(TeamController::class . '@assignUser', $assignRoute->getActionName());

        $this->assertNotNull($removeRoute);
        $this->assertSame(['POST'], $removeRoute->methods());
        $this->assertSame(TeamController::class . '@removeUser', $removeRoute->getActionName());
    }
}
