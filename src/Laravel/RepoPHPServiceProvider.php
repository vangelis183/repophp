<?php

namespace Vangelis\RepoPHP\Laravel;

use Illuminate\Support\ServiceProvider;
use Vangelis\RepoPHP\Command\PackCommand;
use Vangelis\RepoPHP\Config\RepoPHPConfig;
use Vangelis\RepoPHP\Laravel\Commands\LaravelPackCommand;

class RepoPHPServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../../config/repophp.php' => config_path('repophp.php'),
            ], 'repophp-config');

            // Register Laravel-specific commands
            $this->commands([
                LaravelPackCommand::class,
            ]);
        }
    }

    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../../config/repophp.php', 'repophp');

        // Register singleton for RepoPHPConfig
        $this->app->singleton(RepoPHPConfig::class, function ($app) {
            $config = $app['config']->get('repophp', []);
            return new RepoPHPConfig(
                $config['format'] ?? RepoPHPConfig::FORMAT_PLAIN,
                $config['exclude_patterns'] ?? [],
                $config['respect_gitignore'] ?? true,
                $config['token_counter_path'] ?? null,
                $config['encoding'] ?? RepoPHPConfig::ENCODING_CL100K,
                $config['compress'] ?? false,
                $config['max_tokens_per_file'] ?? 0
            );
        });

        // Register the main class
        $this->app->bind('repophp', function ($app) {
            return new \Vangelis\RepoPHP\RepoPHP(
                $app['config']->get('repophp.repository_path', base_path()),
                $app['config']->get('repophp.output_path'),
                $app['config']->get('repophp.format', RepoPHPConfig::FORMAT_PLAIN),
                $app['config']->get('repophp.exclude_patterns', []),
                $app['config']->get('repophp.respect_gitignore', true),
                null, // Output interface will be injected when used
                $app['config']->get('repophp.encoding', RepoPHPConfig::ENCODING_CL100K),
                $app['config']->get('repophp.compress', false),
                $app['config']->get('repophp.token_counter_path'),
                $app['config']->get('repophp.max_tokens_per_file', 0),
                $app['config']->get('repophp.incremental', false),
                $app['config']->get('repophp.base_file')
            );
        });
    }
}
