<?php

namespace App\Http\Middleware;

use App\Application\Services\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiBusinessHeader
{
    public function __construct(private BusinessContext $businessContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => [],
                'errors' => ['Authentication required.'],
            ], 401);
        }

        $businessId = $request->header('X-Business-Id');

        if ($businessId === null || $businessId === '') {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => [],
                'errors' => ['X-Business-Id header is required.'],
            ], 422);
        }

        $business = $user->businesses()->where('businesses.id', $businessId)->first();

        if ($business === null) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => [],
                'errors' => ['You do not have access to this business.'],
            ], 403);
        }

        $this->businessContext->setBusiness($business, $user);

        return $next($request);
    }
}
