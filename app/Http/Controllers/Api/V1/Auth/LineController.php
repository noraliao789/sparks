<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\ResponseCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuthService;
use App\Supports\TokenSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LineController extends Controller
{
    public function redirectUrl(): \Illuminate\Http\JsonResponse
    {
        $state = Str::random(40);
        Cache::put("line_oauth_state:{$state}", 1, now()->addMinutes(10));

        $url = Socialite::driver('line')
            ->stateless()
            ->with(['state' => $state])
            ->redirectUrl(config('services.line.redirect'))
            ->redirect()
            ->getTargetUrl();

        return returnSuccess(['url' => $url]);
    }

    /**
     * @throws ApiException
     * @throws \Exception
     */
    final public function handleCallback(AuthService $service)
    {
        $state = (string)request()->query('state', '');
        if (!Cache::pull("line_oauth_state:{$state}")) {
            returnError(
                ResponseCode::ThirdPartyServiceError,
                'Invalid state',
                422,
            );
        }
        try {
            $providerUser = Socialite::driver('line')->stateless()->user();
        } catch (\Throwable $e) {
            returnError(
                \App\Enums\ResponseCode::ThirdPartyServiceError,
                'Line OAuth failed',
                422,
            );
        }

        $providerId = $providerUser->getId();
        $email = $providerUser->getEmail();
        $name = $providerUser->getName() ?: 'LINE User';
        $avatar = $providerUser->getAvatar();

        $social = SocialAccount::where('provider', 'line')
            ->where('provider_user_id', $providerId)
            ->first();

        if ($social) {
            $user = $social->user;
        } else {
            $user = null;
            if ($email) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email ?? ('line_' . $providerId . '@example.local'),
                    'password' => Hash::make(Str::random(32)),
                ]);
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => 'line',
                'provider_user_id' => $providerId,
                'email' => $email,
                'name' => $name,
                'avatar' => $avatar,
                'raw' => json_encode($providerUser->user, JSON_UNESCAPED_UNICODE),
            ]);
        }

        $clientType = get_user_agent();

        $plainToken = $user->createToken(
            name: "bearer:{$clientType}",
            abilities: ['*'],
        )->plainTextToken;
        $user->update([
            'avatar' => $providerUser->getAvatar(),
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
        [$tokenId, $token] = explode('|', $plainToken);

        TokenSupport::onLoginIssued($user, $clientType, $plainToken);
        Cache::forget("line_oauth_state:{$state}");
        return returnSuccess([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * 綁定:已登入狀態下，產生 LINE 授權 URL（state 帶 link_code）
     *
     * @param Request $request The incoming HTTP request instance.
     * @return \Illuminate\Http\JsonResponse A JSON response with the generated LINE authorization URL.
     */
    final public function linkRedirect()
    {
        $userId = Auth::id();

        $linkCode = Str::random(40);
        Cache::put("line_link:{$linkCode}", $userId, now()->addMinutes(5));

        $url = Socialite::driver('line')
            ->stateless()
            ->with(['state' => $linkCode])
            ->redirectUrl(config('services.line.bind_redirect'))
            ->redirect()
            ->getTargetUrl();

        return returnSuccess(['url' => $url]);
    }

    /**
     * 綁定: LINE callback：用 state 找到 user_id，把 LINE provider 綁到user
     *
     * @param Request $request The incoming HTTP request containing the state and authentication data.
     * @return \Illuminate\Http\JsonResponse A redirect response to a success or error page.
     *
     * @throws ApiException
     */
    public function linkCallback(Request $request)
    {
        $state = (string)$request->query('state', '');
        $userId = Cache::pull("line_link:{$state}");

        if (!$userId) {
            // state 過期或不存在
            returnError(ResponseCode::ThirdPartyServiceError, 'Invalid state', 422);
        }

        $providerUser = Socialite::driver('line')
            ->stateless()
            ->redirectUrl(config('services.line.bind_redirect'))
            ->user();

        $providerUserId = $providerUser->getId();

        SocialAccount::updateOrCreate(
            [
                'provider' => 'line',
                'provider_user_id' => $providerUserId,
            ],
            [
                'user_id' => $userId,
                'name' => $providerUser->getName(),
                'avatar' => $providerUser->getAvatar(),
            ],
        );

        return returnSuccess();
    }
}
