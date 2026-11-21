<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUtf8ContentType
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        if ($contentType === '' || str_starts_with($contentType, 'text/html')) {
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        }

        return $response;
    }
}
