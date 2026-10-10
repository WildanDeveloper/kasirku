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
                // 'unsafe-eval' wajib untuk Alpine (evaluasi expression).
                "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob:",
                "font-src 'self' data:",
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