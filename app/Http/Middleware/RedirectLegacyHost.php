<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectLegacyHost
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        if ($host === 'atelier-aubin.fr'
            || str_starts_with($host, 'new.')
            || str_starts_with($host, 'www.new.')) {
            $target = 'https://www.atelier-aubin.fr' . $request->getRequestUri();

            return redirect($target, 301);
        }

        return $next($request);
    }
}
