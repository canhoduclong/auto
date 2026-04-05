<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = ['vi', 'en'];

        // Nếu có lang trên URL → ưu tiên
        if ($request->has('lang') && in_array($request->get('lang'), $availableLocales, true)) {
            $locale = $request->get('lang');
            $request->session()->put('locale', $locale);
        } 
        // Nếu đã có trong session
        elseif ($request->session()->has('locale') && in_array($request->session()->get('locale'), $availableLocales, true)) {
            $locale = $request->session()->get('locale');
        } 
        // Fallback
        else {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}