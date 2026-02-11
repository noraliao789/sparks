<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Supports\TokenSupport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectUrl()
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return returnSuccess(['url' => $url]);
    }

    /**
     * @throws ApiException
     * @throws \JsonException
     */
    final public function handleCallback(): \Illuminate\Http\JsonResponse
    {
        try {
            $providerUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            returnError(
                \App\Enums\ResponseCode::ThirdPartyServiceError,
                $e->getMessage(),
                422,
            );
        }

        $social = SocialAccount::where('provider', 'google')
            ->where('provider_user_id', $providerUser->getId())
            ->first();

        if ($social) {
            $user = $social->user;
        } else {
            $user = User::where('email', $providerUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $providerUser->getName(),
                    'email' => $providerUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                ]);
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => 'google',
                'provider_user_id' => $providerUser->getId(),
                'email' => $providerUser->getEmail(),
                'name' => $providerUser->getName(),
                'avatar' => $providerUser->getAvatar(),
                'raw' => json_encode($providerUser->user, JSON_THROW_ON_ERROR),
            ]);
        }

        $clientType = get_user_agent();
        $user->update([
            'avatar' => $providerUser->getAvatar(),
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
        $plainToken = $user->createToken(
            name: "bearer:{$clientType}",
            abilities: ['*'],
        )->plainTextToken;

        TokenSupport::onLoginIssued($user, $clientType, $plainToken);
        [$tokenId, $token] = explode('|', $plainToken);

        return returnSuccess([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
