<?php

namespace App\Http\Middleware;

use App\Support\AccessControl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        if (AccessControl::allows($module, $action) || AccessControl::allows('*', $action)) {
            return $next($request);
        }

        abort(403);
    }
}
