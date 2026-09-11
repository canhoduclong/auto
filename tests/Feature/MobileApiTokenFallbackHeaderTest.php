<?php

namespace Tests\Feature;

use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiTokenFallbackHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_api_accepts_the_fallback_token_header(): void
    {
        $user = User::factory()->create();
        $plainTextToken = MobileApiToken::generatePlainTextToken();
        MobileApiToken::create([
            'user_id' => $user->id,
            'name' => 'Android Flutter',
            'token_hash' => MobileApiToken::hashToken($plainTextToken),
        ]);

        $this->withHeader('X-Mobile-Token', $plainTextToken)
            ->getJson('/api/mobile/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_mobile_api_accepts_the_fallback_token_in_a_json_post_body(): void
    {
        $user = User::factory()->create();
        $plainTextToken = MobileApiToken::generatePlainTextToken();
        MobileApiToken::create([
            'user_id' => $user->id,
            'name' => 'Android Flutter',
            'token_hash' => MobileApiToken::hashToken($plainTextToken),
        ]);

        $this->postJson('/api/mobile/auth/refresh', [
            'mobile_token' => $plainTextToken,
        ])->assertOk()->assertJsonPath('data.token_type', 'Bearer');
    }
}