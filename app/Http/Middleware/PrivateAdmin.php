<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivateAdmin
{
    public function handle(Request $r, Closure $next)
    {
        if (! $r->user()) {
            return redirect('/admin/login');
        }
        $r->user()->refresh();
        if (! $r->user()->active || $r->session()->get('account_version') !== $r->user()->session_version) {
            Auth::logout();
            $r->session()->invalidate();

            return redirect('/admin/login');
        }
        if ($r->user()->must_change_password && ! $r->is('admin/password', 'admin/logout')) {
            return redirect('/admin/password');
        }

        return $next($r)->header('Cache-Control', 'private, no-store');
    }
}
