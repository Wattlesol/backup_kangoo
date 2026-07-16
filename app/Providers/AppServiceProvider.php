<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Models\Store;
use App\Observers\OrderObserver;
use App\Observers\StoreObserver;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));

            if (parse_url(config('app.url'), PHP_URL_SCHEME) === 'https') {
                URL::forceScheme('https');
            }
        }

        // Register model observers
        Order::observe(OrderObserver::class);
        Store::observe(StoreObserver::class);

        // Register view composers
        view()->composer('*', \App\View\Composers\ThemeComposer::class);
    }
}
