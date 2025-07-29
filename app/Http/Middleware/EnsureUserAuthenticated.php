<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            // Pour les API, on peut retourner une erreur JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Vous devez être connecté pour accéder à cette ressource.'
                ], 401);
            }
            
            // Pour les requêtes web, rediriger vers la page de connexion
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        return $next($request);
    }
}
