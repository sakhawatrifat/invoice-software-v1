<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\View\Composers\GlobalComposer;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', GlobalComposer::class);
    }

    public function register(): void
    {
        //
    }
}
