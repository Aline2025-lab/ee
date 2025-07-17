<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRol
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, Role $roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->libelle, $roles)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return $next($request);

    }
}
