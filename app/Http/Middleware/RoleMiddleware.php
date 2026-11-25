<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $allowedRoles = collect($roles)
            ->flatMap(fn($role) => explode(',', $role))
            ->map(fn($role) => strtolower(trim($role)))
            ->filter()
            ->all();

        $userRole = strtolower((string) ($user->role ?? ''));

        if (!in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Unauthorized action for your role.');
        }

        return $next($request);
    }
}