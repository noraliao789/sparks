<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Supports\TokenSupport;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            returnError(ResponseCode::TokenRequired, '', 401);
        }

        $user = TokenSupport::resolveUser($token);
        Auth::setUser($user);

        return $next($request);
    }
}
