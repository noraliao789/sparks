<?php

namespace Tests\Feature\Auth;

use App\Enums\ResponseCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Mockery;
use Tests\TestCase;

class LineAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_url_returns_url(): void
    {
        $provider = Mockery::mock(Provider::class);

        $redirectResponse = new class
        {
            public function getTargetUrl(): string
            {
                return 'https://access.line.me/oauth2/v2.1/authorize?...';
            }
        };

        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn($redirectResponse);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('line')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $factory);

        $res = $this->getJson('/api/v1/auth/line/redirect');

        $res->assertStatus(200)
            ->assertJsonPath('code', ResponseCode::Success)
            ->assertJsonStructure(['code', 'data' => ['url']]);
    }

    public function test_callback_creates_user_and_social_account_and_returns_token(): void
    {
        $providerUser = Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive('getId')->andReturn('line-123');
        $providerUser->shouldReceive('getEmail')->andReturn(null);
        $providerUser->shouldReceive('getName')->andReturn('Line Tester');
        $providerUser->shouldReceive('getAvatar')->andReturn('https://example.com/line-avatar.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($providerUser);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('line')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $factory);

        $res = $this->getJson('/api/v1/auth/line/callback');

        $res->assertStatus(200)
            ->assertJsonPath('code', ResponseCode::Success)
            ->assertJsonStructure(['code', 'data' => ['token', 'user']]);

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'line',
            'provider_user_id' => 'line-123',
        ]);

        $token = $res->json('data.token');
        $this->assertNotEmpty($token);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
