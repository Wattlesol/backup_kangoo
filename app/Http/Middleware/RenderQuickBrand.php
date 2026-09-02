<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RenderQuickBrand
{
    /**
     * Keep legacy internal identifiers and historical records intact while
     * enforcing the final Quick brand in rendered HTML.
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if (stripos($contentType, 'text/html') === false || !method_exists($response, 'getContent')) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            return $response;
        }

        $content = preg_replace('/\bSanad\b/u', 'Quick', $content);
        $content = preg_replace('/\bSANAD-/u', 'QUICK-', $content);
        $content = preg_replace('/(?<!\p{L})سند(?!\p{L})/u', 'كويك', $content);

        $response->setContent($content);

        return $response;
    }
}
