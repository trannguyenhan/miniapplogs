<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user() || $request->user()->role !== $role) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
