<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhook clients (WordPress form plugins, Zapier…) usually post form data
 * without an `Accept: application/json` header. Without this, a validation
 * error would answer with a 302 redirect and a 404/429 with an HTML page.
 */
final class ExpectsJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
