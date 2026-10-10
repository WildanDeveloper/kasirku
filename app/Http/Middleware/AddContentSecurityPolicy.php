<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content-Security-Policy dengan nonce per-request.
 *
 * Script inline di view diberi nonce sehingga 'unsafe-inline' tidak perlu,
 * namun Alpine.js tetap memerlukan 'unsafe-eval' untuk mengeksekusi expression.
 */
class AddContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));

        // Dibagikan ke seluruh view (termasuk komponen & halaman error).
        View::share('cspNonce', $nonce);

        $response = $next($request);

        if (str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                // Alpine CSP build: tanpa eval, script inline wajib punya nonce.
                "script-src 'self' 'nonce-{$nonce}'",
                // Font Figtree dimuat dari bunny.net (BUG-38).
                "style-src 'self' https://fonts.bunny.net",
                "img-src 'self' data: blob:",
                "font-src 'self' data: https://fonts.bunny.net",
                "connect-src 'self'",
                "worker-src 'self' blob:",
                "child-src 'self' blob:",
                "media-src 'self' blob:",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]));
        }

        return $response;
    }
}