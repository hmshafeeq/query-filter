<?php

namespace VelitSol\EloquentFilter;

use Illuminate\Support\ServiceProvider;

class  FiltrableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/filterable.php' => config_path('filterable.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filterable.php',
            'filter'
        );
    }

}