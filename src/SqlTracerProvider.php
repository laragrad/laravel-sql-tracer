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
        // Registering SqlTracer as singleton
        $this->app->singleton(SqlTracer::class, function ($app) {
            return new SqlTracer();
        });

        // Registering disk sql-tracer in filesystems config
        config([
            'filesystems.disks' => array_merge(['sql-tracer' => [
                'driver' => 'local',
                'root' => storage_path('sql-tracer'),
                'throw' => false,
            ]], config('filesystems.disks', [])),
        ]);
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
        ], 'sql-tracer-config');

        // Merge vendor default config with published customized config
        $this->mergeConfigFrom(__DIR__.'/../config/sql-tracer.php', 'laragrad.sql-tracer');

        if (config('laragrad.sql-tracer.enable') === true) {
            $sqlTracer = $this->app->make(SqlTracer::class);
            \DB::listen(\Closure::fromCallable([$sqlTracer, 'traceQuery']));
        }
    }
}
