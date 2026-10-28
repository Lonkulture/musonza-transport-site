<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $settings = [];
            if (Schema::hasTable('settings')) {
                $settings = DB::table('settings')->pluck('value', 'key')->all();
            }$view->with('company', array_merge(config('company'), $settings));
        });
    }
}
