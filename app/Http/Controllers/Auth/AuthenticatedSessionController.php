<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = \Auth::user();
        if ($user && !empty($user->language_option)) {
            session(['locale' => $user->language_option]);
            $dir = in_array($user->language_option, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) ? 'rtl' : 'ltr';
            session(['dir' => $dir]);
            \App::setLocale($user->language_option);
        } else {
            session(['locale' => 'ar', 'dir' => 'rtl']);
            \App::setLocale('ar');
        }

        if($request->login == 'user_login' && in_array($user->user_type, ['user', 'customer'], true)){
            return redirect()->intended(route('customer-portal.dashboard'));
        }
        elseif($request->login == 'user_login' && !in_array($user->user_type, ['user', 'customer'], true)) {
            Auth::logout();
            return redirect()->back()->withErrors(['message' => 'You are not allowed to log in from here.']);
        }
        else{
            // For admin/provider login, always go to dashboard
            return redirect(RouteServiceProvider::HOME);
        }
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }
}
