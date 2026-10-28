<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $r, Closure $next)
    {
        $v = $next($r);
        $v->headers->set('X-Content-Type-Options', 'nosniff');
        $v->headers->set('X-Frame-Options', 'DENY');
        $v->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $v->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        if ($r->is('admin*')) {
            $v->headers->set('Cache-Control', 'private, no-store');
        }

return $v;
    }
}
