<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Ambil role user dari database
        $userRole = $request->user()->role->name;

        // cek validasi role
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied, you need Role: ' . implode(' or ', $roles),
                'your_role' => $userRole
            ], 403);
        }
        return $next($request);
    }
}
