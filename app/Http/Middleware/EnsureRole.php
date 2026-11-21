<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces role-based and approval-status access control on protected routes.
 */
class EnsureRole
{
    /**
     * Verify the authenticated user is approved and holds the required role.
     *
     * @param Request $request Incoming HTTP request.
     * @param Closure $next    Next middleware handler.
     * @param string  $role    Required role ('admin' or 'penyewa').
     *
     * @return Response HTTP response or 403 abort when unauthorized.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || !$user->isApproved() || $user->role !== $role) {
            abort(403, 'Unauthorized access for your account role or status.');
        }

        return $next($request);
    }
}
