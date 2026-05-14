<?php

namespace Zakhir\LaravelZakhir;

use Illuminate\Support\ServiceProvider;
use Zakhir\LaravelZakhir\Contracts\ZakhirClientInterface;
use Zakhir\LaravelZakhir\Http\ZakhirClient;
use Zakhir\LaravelZakhir\Http\ZakhirConfig;
use Zakhir\LaravelZakhir\Support\ZakhirLogger;

class ZakhirServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/zakhir.php', 'zakhir');

        $this->app->singleton(ZakhirConfig::class, function ($app): ZakhirConfig {
            return new ZakhirConfig($app['config']->get('zakhir', []));
        });

        $this->app->singleton(ZakhirLogger::class, function ($app): ZakhirLogger {
            $config = $app->make(ZakhirConfig::class);

            return new ZakhirLogger($config->loggingEnabled());
        });

        $this->app->singleton(ZakhirClientInterface::class, function ($app): ZakhirClient {
            return new ZakhirClient(
                config: $app->make(ZakhirConfig::class),
                logger: $app->make(ZakhirLogger::class),
            );
        });

        $this->app->singleton(ZakhirPaymentService::class, function ($app): ZakhirPaymentService {
            return new ZakhirPaymentService(
                client: $app->make(ZakhirClientInterface::class),
                config: $app->make(ZakhirConfig::class),
            );
        });

        // Alias for the Facade
        $this->app->alias(ZakhirPaymentService::class, 'zakhir');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishConfig();
            $this->publishMigrations();
        }

        $this->loadMigrations();
        $this->registerRoutes();
    }

    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/zakhir.php' => config_path('zakhir.php'),
        ], 'zakhir-config');
    }

    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'zakhir-migrations');
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerRoutes(): void
    {
        if (! config('zakhir.routes.enabled', true)) {
            return;
        }

        \Illuminate\Support\Facades\Route::group($this->routeConfig(), function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function routeConfig(): array
    {
        return [
            'prefix'     => config('zakhir.routes.prefix', 'api/zakhir'),
            'middleware' => config('zakhir.routes.middleware', ['api']),
            'as'         => 'zakhir.',
        ];
    }
}
