<?php

namespace App\Http\Middleware;

use App\Models\StaffMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkerAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = StaffMember::query()
            ->with('property.tenantAccount')
            ->find(session('worker_staff_member_id'));

        if (! $staff || $staff->status !== 'active' || ! $staff->mobile_access_enabled) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sessão expirada.'], 401);
            }

            return redirect()->route('worker.login');
        }

        $tenant = $staff->property?->tenantAccount;

        if ($tenant && ! $tenant->hasModule('mobile-app')) {
            abort(403);
        }

        $request->attributes->set('worker_staff_member', $staff);

        return $next($request);
    }
}
