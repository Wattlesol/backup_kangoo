<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LanguageTranslator
{
    public function handle(Request $request, Closure $next)
    {
        $cookieLocale = $request->cookie('quick_locale');

        if (in_array($cookieLocale, ['ar', 'en'], true)) {
            $locale = $cookieLocale;
            session()->put('locale', $locale);
        } elseif (session()->has('locale')) {
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

        // Keep the visual preference server-visible so every Blade page renders
        // in the selected mode before its JavaScript executes. Light is the
        // intentional first-visit default for Quick.
        $cookieTheme = $request->cookie('quick_theme');
        $sessionTheme = session()->get('quick_theme');
        $theme = in_array($cookieTheme, ['light', 'dark'], true)
            ? $cookieTheme
            : (in_array($sessionTheme, ['light', 'dark'], true) ? $sessionTheme : 'light');
        session()->put('quick_theme', $theme);

        return $next($request);
    }
}
