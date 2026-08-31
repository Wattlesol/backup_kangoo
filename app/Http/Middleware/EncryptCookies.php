<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        // These non-sensitive UI preferences are written by browser JavaScript
        // and must remain readable by the language/theme middleware.
        'quick_locale',
        'quick_theme',
    ];
}
