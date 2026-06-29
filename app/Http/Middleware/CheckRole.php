<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$roles  One or more allowed roles (pelanggan, penjual, pemilik, admin)
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null,
            ], 401);
        }

        if (!in_array(Auth::user()->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Required role: ' . implode(', ', $roles),
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
