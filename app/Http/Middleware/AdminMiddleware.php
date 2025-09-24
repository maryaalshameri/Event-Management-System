<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
  public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'غير مصرح بالدخول'], 401);
        }

        if (!Auth::user()->isAdmin()) {
            return response()->json(['message' => 'صلاحية غير كافية'], 403);
        }

        return $next($request);
    }
}
