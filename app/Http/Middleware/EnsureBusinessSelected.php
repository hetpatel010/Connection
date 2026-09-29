<?php

namespace App\Http\Middleware;

use App\Application\Services\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSelected
{
    public function __construct(private BusinessContext $businessContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->businessContext->hasBusiness()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No business selected.',
                    'errors' => [],
                ], 403);
            }

            return redirect()->route('onboarding.business');
        }

        return $next($request);
    }
}
