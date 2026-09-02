<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserType
{
    public function handle(Request $request, Closure $next, ...$allowedTypes)
    {
        $user = $request->user();

        abort_unless(
            $user && in_array((string) $user->user_type, $allowedTypes, true),
            403
        );

        return $next($request);
    }
}
