<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetSeo
{
    /** Fiecare cerere incepe cu starea implicita: noindex, fara titlu propriu. */
    public function handle(Request $request, Closure $next): Response
    {
        app(Seo::class)->reset();

        return $next($request);
    }
}
