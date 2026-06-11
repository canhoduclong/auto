<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ForbiddenRedirectTest extends TestCase
{
    public function test_web_403_redirects_authenticated_user_to_home(): void
    {
        Route::middleware('web')->get('/testing/forbidden-web', function () {
            abort(403, 'Bạn không có quyền truy cập khu vực này.');
        });

        $user = User::factory()->make(['id' => 999]);

        $this->actingAs($user)
            ->get('/testing/forbidden-web')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', 'Bạn không có quyền truy cập khu vực này.');
    }

    public function test_api_403_remains_json_response(): void
    {
        Route::middleware('api')->get('/api/testing/forbidden-json', function () {
            abort(403, 'Forbidden API');
        });

        $this->getJson('/api/testing/forbidden-json')
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden API');
    }
}
