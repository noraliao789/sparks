<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Enums\ResponseCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\LinePushService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Random\RandomException;

class VerificationController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();

        $lineSocial = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'line')
            ->first();

        return returnSuccess([
            'line_bound' => (bool) ($lineSocial?->line_user_id),
            'verified' => ! is_null($user->line_verified_at),
        ]);
    }

    /**
     * @throws RandomException
     * @throws ConnectionException
     * @throws RequestException
     * @throws ApiException
     */
    public function sendOtp(Request $request, LinePushService $line)
    {
        $user = $request->user();

        // 1) 必須先綁定 LINE userId
        $lineSocial = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'line')
            ->first();

        if (! $lineSocial || ! $lineSocial->line_user_id) {
            returnError(ResponseCode::ValidateFailed, 'LINE is not bound yet', 422);
        }

        // 2) 已認證就不需要再發
        if (! is_null($user->line_verified_at)) {
            return returnSuccess(['sent' => false, 'message' => 'Already verified']);
        }

        // 3) 產生 OTP + 存 5 分鐘
        $otp = (string) random_int(100000, 999999);
        Cache::put("otp:line_verify:{$user->id}", password_hash($otp, PASSWORD_BCRYPT), now()->addMinutes(5));

        // 4) 推播到 LINE
        $line->pushText($lineSocial->line_user_id, "你的驗證碼：{$otp}（5分鐘內有效）");

        return returnSuccess(['sent' => true]);
    }

    /**
     * @throws ApiException
     */
    public function verifyOtp(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $hash = Cache::get("otp:line_verify:{$user->id}");

        if (! $hash || ! password_verify($data['otp'], $hash)) {
            returnError(ResponseCode::ValidateFailed, 'Invalid or expired OTP', 422);
        }

        Cache::forget("otp:line_verify:{$user->id}");

        $user->forceFill([
            'line_verified_at' => now(),
        ])->save();

        return returnSuccess([
            'verified' => true,
            'verified_at' => $user->line_verified_at,
        ]);
    }
}
