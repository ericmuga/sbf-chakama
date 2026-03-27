<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChakamaMemberAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('filament.chakama-portal.auth.login');
        }

        $member = $request->user()->member;

        if (! $member || ! $member->is_chakama) {
            abort(403, 'You do not have Chakama Ranch portal access.');
        }

        return $next($request);
    }
}
