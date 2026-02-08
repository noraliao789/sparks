<?php

namespace App\Supports;

use App\Enums\RedisKey;
use App\Enums\RedisTtl;
use App\Enums\ResponseCode;
use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\PersonalAccessToken;

class TokenSupport
{
    public static function onLoginIssued(
        User $user,
        string $clientType,
        string $plainToken
    ): void {
        $clientType = self::normalizeClientType($clientType);
        $newHash = self::hashToken($plainToken);
        $idKey = self::idKey((int) $user->id, $clientType);

        $oldHash = Redis::get($idKey);
        if (! empty($oldHash) && (string) $oldHash !== $newHash) {
            self::revokeByHash((string) $oldHash, $idKey);
        }

        self::cacheTokenByHash($newHash, $user, $clientType);

        PersonalAccessToken::where('tokenable_id', (int) $user->id)
            ->where('tokenable_type', User::class)
            ->whereJsonContains('abilities', $clientType)
            ->where('token', '!=', $newHash)
            ->delete();
    }

    /**
     * Middleware 用：解析 Bearer plain token -> User
     * Redis -> Sanctum -> 回填 Redis
     *
     * @throws \Exception
     */
    public static function resolveUser(string $plainToken): User
    {
        $hash = self::hashToken($plainToken);

        $info = self::getInfoByHash($hash);
        if ($info && ! empty($info['id']) && ! empty($info['client_type']) && ! empty($info['model'])) {
            $user = self::unserializeUser($info['model']);

            if (! $user) {
                self::revokeByHash($hash, self::idKey((int) $info['id'], (string) $info['client_type']));
                throw new \Exception('', ResponseCode::TokenNotFound);
            }

            self::touchByHash($hash, (int) $info['id'], (string) $info['client_type']);

            return $user;
        }

        $pat = PersonalAccessToken::findToken($plainToken);
        if (! $pat) {
            throw new \Exception('', ResponseCode::TokenNotFound);
        }

        $user = $pat->tokenable;
        if (! $user instanceof User) {
            throw new \Exception('', ResponseCode::TokenNotFound);
        }

        $clientType = self::resolveClientType($pat);

        $idKey = self::idKey((int) $user->id, $clientType);
        $currentHash = Redis::get($idKey);

        if (! empty($currentHash) && (string) $currentHash !== $hash) {
            throw new \Exception('', ResponseCode::TokenNotFound);
        }

        self::cacheTokenByHash($hash, $user, $clientType);
        self::touchByHash($hash, (int) $user->id, $clientType);

        return $user;
    }

    /**
     * 登出：用 plain token 清掉 Redis + Sanctum DB
     */
    public static function revokeByPlainToken(string $plainToken, int $userId, string $clientType): void
    {
        $hash = self::hashToken($plainToken);
        $idKey = self::idKey($userId, self::normalizeClientType($clientType));

        $currentHash = Redis::get($idKey);
        if (! empty($currentHash) && (string) $currentHash === $hash) {
            self::revokeByHash($hash, $idKey);

            return;
        }

        Redis::pipeline(static function ($pipe) use ($hash) {
            $pipe->del(self::lockKey($hash));
            $pipe->del(self::tokenKey($hash));
        });
        PersonalAccessToken::where('token', $hash)->delete();
    }

    // -------------------------
    // Internal
    // -------------------------

    private static function normalizeClientType(string $clientType): string
    {
        $clientType = strtolower(trim($clientType));

        return $clientType !== '' ? $clientType : 'app';
    }

    private static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private static function tokenKey(string $hash): string
    {
        return RedisKey::AUTH_USER_TOKEN.$hash;
    }

    private static function idKey(int $userId, string $clientType): string
    {
        return RedisKey::AUTH_USER_ID."{$userId}:{$clientType}";
    }

    private static function lockKey(string $hash): string
    {
        return RedisKey::LOCK_TOKEN_CHECK.$hash;
    }

    private static function getInfoByHash(string $hash): ?array
    {
        $data = Redis::hGetAll(self::tokenKey($hash));

        return empty($data) ? null : $data;
    }

    private static function resolveClientType(PersonalAccessToken $token): string
    {
        $abilities = $token->abilities ?? [];
        $clientType = (string) ($abilities[0] ?? 'app');

        return self::normalizeClientType($clientType);
    }

    private static function cacheTokenByHash(string $hash, User $user, string $clientType): void
    {
        $tokenKey = self::tokenKey($hash);
        $idKey = self::idKey((int) $user->id, $clientType);

        $userInfo = [
            'id' => (string) $user->id,
            'client_type' => $clientType,
            'model' => serialize($user),
        ];

        $ttlToken = RedisTtl::AUTH_TOKEN;
        $ttlId = RedisTtl::AUTH_USER;

        Redis::pipeline(static function ($pipe) use ($tokenKey, $idKey, $userInfo, $ttlToken, $ttlId, $hash) {
            $pipe->hMSet($tokenKey, $userInfo);
            $pipe->expire($tokenKey, $ttlToken);

            $pipe->setex($idKey, $ttlId, $hash);
        });
    }

    private static function touchByHash(string $hash, int $userId, string $clientType): void
    {
        $lockKey = self::lockKey($hash);
        $ttlLock = RedisTtl::TOKEN_LOCK;

        if (Redis::exists($lockKey)) {
            return;
        }

        Redis::setex($lockKey, $ttlLock, '1');

        Redis::expire(self::tokenKey($hash), RedisTtl::AUTH_TOKEN);
        Redis::expire(self::idKey($userId, $clientType), RedisTtl::AUTH_USER);
    }

    /**
     * 真正 revoke：刪 Redis + 刪 Sanctum DB row
     */
    private static function revokeByHash(string $hash, string $idKey): void
    {
        Redis::pipeline(static function ($pipe) use ($hash, $idKey) {
            $pipe->del(self::lockKey($hash));
            $pipe->del(self::tokenKey($hash));
            $pipe->del($idKey);
        });

        PersonalAccessToken::where('token', $hash)->delete();
    }

    private static function unserializeUser(?string $serialized): ?User
    {
        if (! $serialized) {
            return null;
        }

        try {
            $user = unserialize($serialized, ['allowed_classes' => true]);
        } catch (\Throwable) {
            return null;
        }

        return $user instanceof User ? $user : null;
    }
}
