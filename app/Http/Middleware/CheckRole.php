<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
           try{
           $user = JWTAuth::parseToken()->authenticate(); // Jangan lupa import kelas facades nya

           if (!in_array($user->role, $roles)){  // Cek Roles
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized Access'
            ], 403);
           }
           return $next($request);
        } catch(JWTException $e) { // Jika tokennya tidak valid lagi /habis waktu
            return response()->json([
               'success' => false,
               'message' => 'Token is Invalid or Expired'
            ], 401);

        }
    }
}
