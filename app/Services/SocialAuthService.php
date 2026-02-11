<?php

namespace App\Services;

use App\Enums\ResponseCode;
use App\Enums\SocialProvider;
use App\Models\SocialAccount;
use App\Models\User;
use App\Supports\TokenSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthService
{
    /**
     * Social login:
     * - 先用 provider_user_id 找 social_accounts
     * - 再用 email 合併（
     * - 建/更新 social_accounts
     * @throws \Throwable
     */
    public function login(
        string|SocialProvider $provider,
        string $providerUserId,
        ?string $email,
        ?string $name,
        ?string $avatar,
        array $rawUser,
        string $clientType
    ): array {
        return DB::transaction(function () use (
            $provider,
            $providerUserId,
            $email,
            $name,
            $avatar,
            $rawUser,
            $clientType
        ) {
            $social = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($social) {
                $user = $social->user;
                if (!$user) {
                    returnError(ResponseCode::TokenNotFound, 'user model is not complete', 401);
                }

                $social->fill([
                    'email' => $email,
                    'name' => $name,
                    'avatar' => $avatar,
                    'raw' => json_encode($rawUser, JSON_UNESCAPED_UNICODE),
                ]);
                $social->save();

                return $this->issueTokenAndUpdateUser($user, $avatar, $clientType);
            }

            $user = null;
            if ($email) {
                $user = User::query()->where('email', $email)->first();
            }

            if (!$user) {
                $user = User::query()->create([
                    'name' => $name ?: strtoupper($provider) . ' User',
                    'email' => $email ?? ($provider . '_' . $providerUserId . '@example.local'),
                    'password' => Hash::make(Str::random(32)),
                ]);
            }

            SocialAccount::query()->create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
                'email' => $email,
                'name' => $name,
                'avatar' => $avatar,
                'raw' => json_encode($rawUser, JSON_UNESCAPED_UNICODE),
            ]);

            return $this->issueTokenAndUpdateUser($user, $avatar, $clientType);
        });
    }

    /**
     * Link provider to existing logged-in user
     * - 禁止轉綁：如果 provider_user_id 已綁到別人，回 409
     * - 已綁到自己：更新 profile
     * - 未存在：建立綁定
     * @throws \Throwable
     */
    public function link(
        int $userId,
        string|SocialProvider $provider,
        string $providerUserId,
        ?string $email,
        ?string $name,
        ?string $avatar,
        array $rawUser
    ): void {
        DB::transaction(function () use (
            $userId,
            $provider,
            $providerUserId,
            $email,
            $name,
            $avatar,
            $rawUser
        ) {
            $existing = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($existing && (int)$existing->user_id !== (int)$userId) {
                returnError(ResponseCode::ThirdPartyServiceError, 'This social account is already linked', 409);
            }

            SocialAccount::query()->updateOrCreate(
                [
                    'provider' => $provider,
                    'provider_user_id' => $providerUserId,
                ],
                [
                    'user_id' => $userId,
                    'email' => $email,
                    'name' => $name,
                    'avatar' => $avatar,
                    'raw' => json_encode($rawUser, JSON_UNESCAPED_UNICODE),
                ],
            );
        });
    }

    /**
     * 發 token + 更新登入資訊 + TokenSupport 單一登入策略
     * @return array{token: string, user: User}
     * @throws \Throwable
     */
    private function issueTokenAndUpdateUser(User $user, ?string $avatar, string $clientType): array
    {
        $plainToken = $user->createToken(
            name: "bearer:{$clientType}",
            abilities: ['*'],
        )->plainTextToken;

        $user->update([
            'avatar' => $avatar,
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        TokenSupport::onLoginIssued($user, $clientType, $plainToken);

        [$tokenId, $token] = explode('|', $plainToken);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
