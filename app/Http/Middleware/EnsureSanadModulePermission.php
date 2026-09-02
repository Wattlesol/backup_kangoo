<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSanadModulePermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'read')
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasAnyRole(['admin', 'demo_admin'])) {
            return $next($request);
        }

        if (!$user->hasSanadModulePermission($module, $action)) {
            abort(403);
        }

        return $next($request);
    }
}
