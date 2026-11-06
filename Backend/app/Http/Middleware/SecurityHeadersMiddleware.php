<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * OnlinePuja.live — Security Headers Middleware
 *
 * Injects security-hardening HTTP headers on every response to
 * protect against XSS, clickjacking, MIME sniffing, and info leakage,
 * while seamlessly authorizing Google Analytics (GA4), Google Tag Manager,
 * and Meta Pixel tracking.
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Protect against clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Force HTTPS for 1 year, include subdomains
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Control browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(self)');

        // Remove server identification
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        // Content Security Policy: allow scripts/styles from self + CDNs + GA4 + Meta Pixel
        if (!$request->is('api/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self';"
                . " script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://checkout.razorpay.com https://api.razorpay.com https://checkout.stripe.com https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net;"
                . " style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net;"
                . " font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com;"
                . " img-src 'self' data: blob: https: http: https://www.facebook.com https://www.google-analytics.com https://www.googletagmanager.com;"
                . " connect-src 'self' https://api.razorpay.com https://api.stripe.com wss://rtc.onlinepuja.live https://www.google-analytics.com https://analytics.google.com https://*.google-analytics.com https://*.analytics.google.com https://*.facebook.com https://*.facebook.net;"
                . " frame-src 'self' https://api.razorpay.com https://checkout.stripe.com https://www.facebook.com;"
                . " object-src 'none';"
                . " base-uri 'self';"
                . " form-action 'self';"
            );
        }

        return $response;
    }
}
