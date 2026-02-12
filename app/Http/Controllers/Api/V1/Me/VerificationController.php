<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Enums\ResponseCode;
use App\Enums\SocialProvider;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\LinePushService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Random\RandomException;

class VerificationController extends Controller
{
    public function lineStatus()
    {
        $userId = Auth::id();
        $lineSocial = SocialAccount::query()
            ->where('user_id', $userId)
            ->where('provider', SocialProvider::Line)
            ->first();
        $user = User::find($userId);

        return returnSuccess([
            'line_bound' => ($lineSocial && $lineSocial->provider_user_id),
            'verified_at' => $user->line_verified_at?->toDateTimeString(),
        ]);
    }

    /**
     * @throws RandomException
     * @throws ConnectionException
     * @throws RequestException
     * @throws ApiException
     */
    public function sendOtp(LinePushService $line)
    {
        $user = Auth::user();

        $lineSocial = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'line')
            ->first();

        if (!$lineSocial || !$lineSocial->provider_user_id) {
            returnError(ResponseCode::ValidateFailed, 'LINE is not bound yet', 422);
        }

        if (!is_null($user->line_verified_at)) {
            return returnSuccess(['sent' => false, 'message' => 'Already verified']);
        }

        $otp = (string)random_int(100000, 999999);
        Cache::put("otp:line_verify:{$user->id}", password_hash($otp, PASSWORD_BCRYPT), now()->addMinutes(5));

        $line->pushText($lineSocial->provider_user_id, "你的驗證碼：{$otp}（5分鐘內有效）");

        return returnSuccess(['sent' => true]);
    }

    /**
     * @throws ApiException
     */
    public function verifyOtp(Request $request)
    {
        $userId = Auth::id();
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $hash = Cache::get("otp:line_verify:{$userId}");

        if (!$hash || !password_verify($data['otp'], $hash)) {
            returnError(ResponseCode::ValidateFailed, 'Invalid or expired OTP', 422);
        }

        Cache::forget("otp:line_verify:{$userId}");

        $user = \App\Models\User::find($userId);
        $user->forceFill([
            'line_verified_at' => now(),
        ])->save();

        return returnSuccess([
            'verified' => true,
            'verified_at' => $user->line_verified_at?->toDateTimeString(),
        ]);
    }
}
