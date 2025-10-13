<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;

use App\Models\User;

use App\Observers\BaseObserver;

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
    public function boot()
    {
        Paginator::useBootstrapFive();
        
        // // Fetch global data
        // $globalData = User::with('company')->where('user_type', 'admin')->first();

        // // Share with all views
        // View::share('globalData', $globalData);

        // Register observers
        User::observe(BaseObserver::class);
    }

}
