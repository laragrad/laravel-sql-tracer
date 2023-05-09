<?php

namespace Laragrad\SqlTracer;

class SqlTracerProvider
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

        if (config('sql-tracer.sql_tracer_enabled') === true) {
            \DB::listen(\Closure::fromCallable([SqlTracer::class, 'traceQuery']));
        }
    }
}
