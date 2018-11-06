<?php

namespace LaraRepo\Repositories\Providers;

use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use LaraRepo\Repositories\Console\Commands\MakeFilterCommand;
use LaraRepo\Repositories\Console\Commands\MakeRepositoryCommand;
use LaraRepo\Repositories\Console\Commands\Creators\FilterCreator;
use LaraRepo\Repositories\Console\Commands\Creators\RepositoryCreator;

/**
 * Class RepositoryProvider
 *
 * @package LaraRepo\Repositories\Providers
 */
class RepositoryProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Config path.
        $config_path = __DIR__ . '/../../../config/repositories.php';

        // Publish config.
        $this->publishes(
            [$config_path => config_path('repositories.php')],
            'repositories'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Register bindings.
        $this->registerBindings();

        // Register make repository command.
        $this->registerMakeRepositoryCommand();

        // Register make filter command.
        $this->registerMakeFilterCommand();

        // Register commands
        $this->commands(['command.repository.make', 'command.filter.make']);

        // Config path.
        $config_path = __DIR__ . '/../../../config/repositories.php';

        // Merge config.
        $this->mergeConfigFrom(
            $config_path,
            'repositories'
        );
    }

    /**
     * Register the bindings.
     */
    protected function registerBindings()
    {
        // FileSystem.
        $this->app->instance('FileSystem', new Filesystem());

        // Composer.
        $this->app->bind('Composer', function ($app)
        {
            return new Composer($app['FileSystem']);
        });

        // Repository creator.
        $this->app->singleton('RepositoryCreator', function ($app)
        {
            return new RepositoryCreator($app['FileSystem']);
        });

        // Filter creator.
        $this->app->singleton('FilterCreator', function ($app)
        {
            return new FilterCreator($app['FileSystem']);
        });
    }

    /**
     * Register the make:repository command.
     */
    protected function registerMakeRepositoryCommand()
    {
        // Make repository command.
        $this->app['command.repository.make'] = $this->app->share(
            function($app)
            {
                return new MakeRepositoryCommand($app['RepositoryCreator'], $app['Composer']);
            }
        );
    }

    /**
     * Register the make:filter command.
     */
    protected function registerMakeFilterCommand()
    {
        // Make filter command.
        $this->app['command.filter.make'] = $this->app->share(
            function($app)
            {
                return new MakeFilterCommand($app['FilterCreator'], $app['Composer']);
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.repository.make',
            'command.filter.make'
        ];
    }
}
