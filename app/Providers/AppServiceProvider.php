<?php

namespace App\Providers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        View::composer([
            'layouts.hierarchylevelheader',
            'layouts.hierarchylevelsidebar',
            'layouts.clientheader',
            'client.visitRepport',
            'client.dashboard',
            'layouts.clientnavbar'
        ], function ($view) {
            $view->with('formatID', Session::get('format_id'))
                ->with('formatName', Session::get('format_Name'))
                ->with('waveID', Session::get('wave_id1'))
                ->with('waveName', Session::get('wave_Name1'))
                ->with('YTD', Session::get('YTD'))
                ->with('user_id', Session::get('user_id'))
                ->with('title', Session::get('title'))
                ->with('location', Session::get('location'))
                ->with('location_name', Session::get('location_name'))
                ->with('level_id', Session::get('level_id'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
