<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && $u->must_change_password && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.edit')->with('warning', 'Vous devez remplacer votre mot de passe temporaire.');
        }
        return $next($request);
    }
}
