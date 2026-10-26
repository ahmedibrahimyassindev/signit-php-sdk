<?php

namespace Signit\Laravel;

use Illuminate\Support\ServiceProvider;
use Signit\SignitClient;

class SignitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/signit.php', 'signit');

        $this->app->singleton(SignitClient::class, function ($app) {
            $config = $app['config']->get('signit', []);

            return new SignitClient(
                $config['client_id'] ?? null,
                $config['client_secret'] ?? null,
                $config['base_url'] ?? null,
                $config['client_query_id'] ?? null,
                $config['timeout'] ?? 30
            );
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/signit.php' => config_path('signit.php'),
            ], 'signit-config');
        }
    }
}
