<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $publicRoutes = [
            'signin',
            'signup',
            'refresh',
            'logout',
            'resend-otp',
            'verify-otp',
            'setup-password',
            'roles',
            'stripe/webhook',
            'learning',
        ];

        $tenantOptionalRoutes = [
            'tenants', // allow creating first tenant
        ];

        $path = $request->path();

        // Skip public routes
        if (in_array($path, $publicRoutes)) {
            return $next($request);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // If user has no tenants yet, allow tenant creation route
        $userTenantsCount = $user->default_tenant()->count();

        if ($userTenantsCount === 0 && in_array($path, $tenantOptionalRoutes) && $request->isMethod('POST')) {
            // No tenant yet, but creating one
            return $next($request);
        }

        // For all other cases, tenant ID is required
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json(['message' => 'Tenant ID is required.'], 400);
        }

        // Validate that this tenant belongs to the user
        $tenant = $user->default_tenant()->where('tenantId', $tenantId)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Invalid or unauthorized tenant.'], 403);
        }

        // Optional: Check if tenant is active
        if ($tenant->status !== 'active') {
            return response()->json(['message' => 'Tenant is not active.'], 403);
        }

        // Bind tenant to request and container
        app()->instance('currentTenant', $tenant);
        $request->merge(['tenant' => $tenant]);

        return $next($request);
    }
}
