<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get language from Accept-Language header or query parameter
        $locale = $request->header('Accept-Language') 
                  ?? $request->query('lang')
                  ?? $request->input('lang')
                  ?? config('app.locale');

        // Support for locales: en, vi
        $supportedLocales = ['en', 'vi'];
        
        // Extract primary language if full locale (e.g., "en-US" -> "en")
        $primaryLocale = substr($locale, 0, 2);
        
        if (in_array($primaryLocale, $supportedLocales)) {
            App::setLocale($primaryLocale);
        } else {
            // Fallback to default locale
            App::setLocale(config('app.locale'));
        }

        return $next($request);
    }
}
