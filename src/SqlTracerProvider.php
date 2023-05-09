<?php

namespace Laragrad\SqlTracer;

use Illuminate\Support\ServiceProvider;

class SqlTracerProvider extends ServiceProvider
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
        $this->publishes([
            __DIR__.'/../config/sql-tracer.php' => config_path('laragrad/sql-tracer.php'),
        ]);

        // Merge vendor default config with published customized config
        $this->mergeConfigFrom(__DIR__.'/../config/sql-tracer.php', 'laragrad.sql-tracer');

        if (config('laragrad.sql-tracer.enable') === true) {
            \DB::listen(\Closure::fromCallable([SqlTracer::class, 'traceQuery']));
        }
    }
}
