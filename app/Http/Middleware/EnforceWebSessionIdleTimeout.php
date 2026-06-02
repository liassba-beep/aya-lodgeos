<?php

namespace App\Http\Middleware;

use App\Support\AuditTrail;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceWebSessionIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = (int) config('auth.web_idle_timeout_minutes', 30);

        if ($timeoutMinutes <= 0) {
            return $next($request);
        }

        $lastActivityAt = (int) $request->session()->get('auth.web_last_activity_at', 0);
        $now = time();

        if ($lastActivityAt > 0 && ($now - $lastActivityAt) > ($timeoutMinutes * 60)) {
            $user = $request->user();
            $propertyId = TenantContext::propertyId();

            AuditTrail::logAccessEvent('session_timeout', $user, $propertyId, [
                'idle_minutes' => $timeoutMinutes,
                'host' => $request->getHost(),
            ]);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sessão expirada.'], 401);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Sessão expirada por inactividade. Inicie sessão novamente.',
            ]);
        }

        $request->session()->put('auth.web_last_activity_at', $now);

        return $next($request);
    }
}
