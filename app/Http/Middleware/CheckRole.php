<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // إذا لم يكن المستخدم مسجلاً أو ليس لديه الدور المطلوب
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'غير مصرح بالوصول. الدور المطلوب: ' . implode(', ', $roles)
            ], 403);
        }
        
        return $next($request);
    }
    }

