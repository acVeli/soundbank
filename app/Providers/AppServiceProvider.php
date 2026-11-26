<?php

namespace App\Providers;

use App\Services\Cart;
use App\Services\Settings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Settings::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        View::composer('layouts.app', function ($view): void {
            $view->with('cartCount', app(Cart::class)->count());
        });
    }
}
