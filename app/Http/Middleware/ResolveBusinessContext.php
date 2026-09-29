<?php

namespace App\Http\Middleware;

use App\Application\Services\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveBusinessContext
{
    public function __construct(private BusinessContext $businessContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            $this->businessContext->resolveForUser($request->user());
        }

        return $next($request);
    }
}
