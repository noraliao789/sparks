<?php

namespace Tests\Feature\Auth;

use App\Enums\ResponseCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_url_endpoint_returns_url(): void
    {
        $provider = Mockery::mock(Provider::class);

        $redirectResponse = new class
        {
            public function getTargetUrl(): string
            {
                return 'https://accounts.google.com/o/oauth2/auth?...';
            }
        };

        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn($redirectResponse);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $factory);

        $res = $this->getJson('/api/v1/auth/google/redirect');

        $res->assertStatus(200)
            ->assertJsonPath('code', ResponseCode::Success)
            ->assertJsonStructure(['code', 'data' => ['url']]);
    }

    public function test_callback_creates_user_social_account_and_returns_token(): void
    {
        $providerUser = Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive('getId')->andReturn('google-123');
        $providerUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $providerUser->shouldReceive('getName')->andReturn('Test User');
        $providerUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($providerUser);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $factory);

        $res = $this->getJson('/api/v1/auth/google/callback');

        $res->assertStatus(200)
            ->assertJsonPath('code', 20000)
            ->assertJsonStructure(['code', 'data' => ['token', 'user']]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-123',
            'email' => 'test@example.com',
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
