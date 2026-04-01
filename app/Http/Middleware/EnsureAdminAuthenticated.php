<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get('admin_logged_in', false)) {
            return redirect()->route('admin.login.form');
        }

        return $next($request);
    }
}
