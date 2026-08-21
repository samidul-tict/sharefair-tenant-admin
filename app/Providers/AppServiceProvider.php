<?php

namespace App\Providers;

use App\Support\AdminContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('backend.layout.inner-app', function ($view) {
            $view->with('logUser', AdminContext::logUser());
            $view->with('menus', AdminContext::menus());
        });
    }
}
