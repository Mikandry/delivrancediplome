<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Permission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        abort_unless($user && $user->actif && $user->hasPermission($permission), 403, "Accès refusé.");
        return $next($request);
    }
}
