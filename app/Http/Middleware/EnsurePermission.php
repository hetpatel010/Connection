<?php

namespace App\Http\Middleware;

use App\Application\Services\BusinessContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function __construct(private BusinessContext $businessContext) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $this->businessContext->userCan($permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
