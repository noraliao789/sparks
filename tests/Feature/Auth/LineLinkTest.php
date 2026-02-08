<?php

namespace Tests\Feature\Auth;

use App\Enums\ResponseCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Mockery;
use Tests\TestCase;

class LineLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_redirect_returns_authorize_url_with_state(): void
    {
        $user = User::factory()->create();

        $provider = Mockery::mock(Provider::class);

        $redirectResponse = new class
        {
            public function getTargetUrl(): string
            {
                return 'https://access.line.me/oauth2/v2.1/authorize?...&state=xxx';
            }
        };

        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn($redirectResponse);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('line')->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $factory);

        $res = $this->actingAs($user)->getJson('/api/v1/auth/line/link/redirect');

        $res->assertStatus(200)
            ->assertJsonPath('code', ResponseCode::Success)
            ->assertJsonStructure(['code', 'data' => ['url']]);
    }

    public function test_link_callback_binds_social_account_to_existing_user(): void
    {
        $user = User::factory()->create();

        $state = 'state_abc';
        Cache::put("line_link:{$state}", $user->id, now()->addMinutes(5));

        $providerUser = Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive('getId')->andReturn('line-login-id-123');
        $providerUser->shouldReceive('getName')->andReturn('Line User');
        $providerUser->shouldReceive('getAvatar')->andReturn('https://example.com/a.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($providerUser);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('line')->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $factory);

        $res = $this->get('/api/v1/auth/line/link/callback?code=fake&state='.$state);

        $res->assertStatus(302);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'line',
            'provider_user_id' => 'line-login-id-123',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
