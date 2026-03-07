<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role === 'member' && ! $user->is_approved) {
            return new JsonResponse(['message' => 'Account is pending approval.'], 403);
        }

        return $next($request);
    }
}

