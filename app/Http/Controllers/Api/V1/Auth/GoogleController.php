<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\ResponseCode;
use App\Enums\SocialProvider;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectUrl()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return returnSuccess(['url' => $url]);
    }

    /**
     * @throws ApiException
     */
    public function handleCallback(SocialAuthService $service)
    {
        try {
            $providerUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            returnError(ResponseCode::ThirdPartyServiceError, 'Google OAuth failed', 422);
        }

        $clientType = get_user_agent();

        $result = $service->login(
            SocialProvider::Google,
            (string)$providerUser->getId(),
            $providerUser->getEmail(),
            $providerUser->getName(),
            $providerUser->getAvatar(),
            (array)($providerUser->user ?? []),
            $clientType,
        );

        return returnSuccess($result);
    }

    /**
     * 綁定：已登入狀態下，產生 Google 授權 URL（state 帶 link_code）
     */
    public function linkRedirect()
    {
        $userId = Auth::id();

        $linkCode = Str::random(40);
        Cache::put("google_link:{$linkCode}", $userId, now()->addMinutes(5));

        // ⚠️ 你要在 config/services.php 補一個 google.bind_redirect
        $url = Socialite::driver('google')
            ->stateless()
            ->with(['state' => $linkCode])
            ->redirectUrl(config('services.google.bind_redirect'))
            ->redirect()
            ->getTargetUrl();

        return returnSuccess(['url' => $url]);
    }

    /**
     * 綁定 callback：用 state 找到 user_id，把 Google provider 綁到 user（禁止轉綁）
     * @throws ApiException
     */
    public function linkCallback(SocialAuthService $service)
    {
        $state = (string)request()->query('state', '');
        $userId = Cache::pull("google_link:{$state}");

        if (!$userId) {
            returnError(ResponseCode::ThirdPartyServiceError, 'Invalid state', 422);
        }

        try {
            $providerUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl(config('services.google.bind_redirect'))
                ->user();
        } catch (\Throwable) {
            returnError(ResponseCode::ThirdPartyServiceError, 'Google OAuth failed', 422);
        }

        $service->link(
            userId: (int)$userId,
            provider: SocialProvider::Google,
            providerUserId: (string)$providerUser->getId(),
            email: $providerUser->getEmail(),
            name: $providerUser->getName(),
            avatar: $providerUser->getAvatar(),
            rawUser: (array)($providerUser->user ?? []),
        );

        return returnSuccess();
    }
}
