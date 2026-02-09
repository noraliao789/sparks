<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Enums\ResponseCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use Illuminate\Http\Request;

class LineBindController extends Controller
{
    /**
     * @throws ApiException
     */
    public function bind(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'line_user_id' => ['required', 'string', 'min:10', 'max:64'],
        ]);

        $social = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'line')
            ->first();

        if (! $social) {
            returnError(ResponseCode::ValidateFailed, 'LINE social account not found for this user', 422);
        }

        $social->forceFill([
            'line_user_id' => $data['line_user_id'],
        ])->save();

        return returnSuccess([
            'line_bound' => true,
        ]);
    }
}
