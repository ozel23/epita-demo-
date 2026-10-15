<?php

namespace App\Http\Middleware;

use Closure;
class ActiveUser
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            $valid = $user->active;
            if ($request->hasSession() && $request->session()->has('security_version')) {
                $valid = $valid && $request->session()->get('security_version') === $user->security_version;
            }
            $token = $user->currentAccessToken();
            if ($token && isset($token->security_version)) {
                $valid = $valid && (int) $token->security_version === (int) $user->security_version;
            }
            if (!$valid) {
                if ($request->hasSession()) {
                    auth('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
                abort(403, 'Akun atau sesi tidak tersedia.');
            }
        }
        return $next($request);
    }
}
