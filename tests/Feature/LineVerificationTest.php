<?php

namespace Tests\Feature;

use App\Enums\ResponseCode;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\LinePushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class LineVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_returns_line_bound_and_verified_flags(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'line',
            'line_user_id' => null,
        ]);

        $res = $this->actingAs($user)->getJson('/api/v1/me/verification');

        $res->assertStatus(200)
            ->assertJsonPath('code', ResponseCode::Success)
            ->assertJsonPath('data.line_bound', false)
            ->assertJsonPath('data.verified', false);
    }

    public function test_send_otp_requires_line_bound(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'line',
            'line_user_id' => null,
        ]);

        $res = $this->actingAs($user)->postJson('/api/v1/me/verification/otp/send');

        $res->assertStatus(422);
    }

    public function test_send_and_verify_otp_success(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'line',
            'line_user_id' => 'U1234567890abcdef',
        ]);

        $mock = Mockery::mock(LinePushService::class);
        $mock->shouldReceive('pushText')
            ->once()
            ->withArgs(function ($to, $text) {
                return $to === 'U1234567890abcdef' && str_contains($text, '你的驗證碼：');
            });
        $this->app->instance(LinePushService::class, $mock);

        $sendRes = $this->actingAs($user)->postJson('/api/v1/me/verification/otp/send');
        $sendRes->assertStatus(200)
            ->assertJsonPath('code', ResponseCode::Success)
            ->assertJsonPath('data.sent', true);

        Cache::put("otp:line_verify:{$user->id}", password_hash('123456', PASSWORD_BCRYPT), now()->addMinutes(5));

        $verifyRes = $this->actingAs($user)->postJson('/api/v1/me/verification/otp/verify', [
            'otp' => '123456',
        ]);

        $verifyRes->assertStatus(200)
            ->assertJsonPath('code', ResponseCode::Success)
            ->assertJsonPath('data.verified', true);

        $user->refresh();
        $this->assertNotNull($user->line_verified_at);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
