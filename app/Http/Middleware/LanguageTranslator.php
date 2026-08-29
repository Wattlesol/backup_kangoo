<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LanguageTranslator
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('locale')) {
            $locale = session()->get('locale');
        } elseif (auth()->check() && !empty(auth()->user()->language_option)) {
            $locale = auth()->user()->language_option;
            session()->put('locale', $locale);
        } else {
            $locale = 'ar';
            session()->put('locale', $locale);
        }

        \App::setLocale($locale);

        $dir = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) ? 'rtl' : 'ltr';
        if (!session()->has('dir') || session()->get('dir') !== $dir) {
            session()->put('dir', $dir);
        }

        return $next($request);
    }
}
