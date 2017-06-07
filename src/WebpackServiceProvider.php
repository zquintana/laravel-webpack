<?php

namespace ZQuintana\LaravelWebpack;

use Illuminate\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use ZQuintana\LaravelWebpack\AssetProvider\BladeDirectoryAssetProvider;
use ZQuintana\LaravelWebpack\AssetProvider\BladeProvider;
use ZQuintana\LaravelWebpack\AssetProvider\DirectoryProvider\ConfiguredDirectoryProvider;
use ZQuintana\LaravelWebpack\AssetProvider\TwigAssetProvider;
use ZQuintana\LaravelWebpack\AssetProvider\TwigDirectoryAssetProvider;
use ZQuintana\LaravelWebpack\Blade\WebpackHelper;
use ZQuintana\LaravelWebpack\Command\CompileCommand;
use ZQuintana\LaravelWebpack\Command\DevServerCommand;
use ZQuintana\LaravelWebpack\Command\SetupCommand;
use ZQuintana\LaravelWebpack\Compiler\WebpackCompiler;
use ZQuintana\LaravelWebpack\Compiler\WebpackProcessBuilder;
use ZQuintana\LaravelWebpack\Config\WebpackConfigDumper;
use ZQuintana\LaravelWebpack\Config\WebpackConfigManager;
use ZQuintana\LaravelWebpack\ErrorHandler\DefaultErrorHandler;
use ZQuintana\LaravelWebpack\ErrorHandler\SuppressingErrorHandler;
use ZQuintana\LaravelWebpack\ErrorHandler\UnknownReferenceIgnoringErrorHandler;
use ZQuintana\LaravelWebpack\Service\AliasManager;
use ZQuintana\LaravelWebpack\Service\AssetCollector;
use ZQuintana\LaravelWebpack\Service\AssetLocator;
use ZQuintana\LaravelWebpack\Service\AssetManager;
use ZQuintana\LaravelWebpack\Service\AssetNameGenerator;
use ZQuintana\LaravelWebpack\Service\AssetResolver;
use ZQuintana\LaravelWebpack\Service\EntryFileManager;
use ZQuintana\LaravelWebpack\Service\ManifestStorage;

/**
 * Class WebpackServiceProvider
 */
class WebpackServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/webpack.php' => config_path('zq_webpack.php'),
        ], 'config');
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $configPath = __DIR__.'/../config/webpack.php';
        $this->mergeConfigFrom($configPath, 'zq_webpack');

        $this->registerProviders()
            ->registerErrorHandlers()
            ->registerAssetServices()
            ->registerConfigs()
            ->registerCompiler()
            ->registerCommands()
            ->registerBladeExtension()
        ;
    }

    /**
     * @return $this
     */
    private function registerAssetServices()
    {
        $this->app->singleton('zq_webpack.asset_collector', function (Container $app) {
            $collector = new AssetCollector($app->make('zq_webpack.error_handler'));
            foreach (config('zq_webpack.asset_providers') as $provider) {
                $collector->addAssetProvider($app->make($provider));
            }

            return $collector;
        });
        $this->app->singleton('zq_webpack.alias_manager', function (Container $app) {
            return new AliasManager(config('zq_webpack.aliases', []));
        });
        $this->app->singleton('zq_webpack.entry_file_manager', function (Container $app) {
            return new EntryFileManager(
                config('zq_webpack.entry_file.enabled_extensions'),
                config('zq_webpack.entry_file.disabled_extensions'),
                config('zq_webpack.entry_file.type_map')
            );
        });
        $this->app->singleton('zq_webpack.asset_name_generator', function (Container $app) {
            return new AssetNameGenerator();
        });
        $this->app->singleton('zq_webpack.asset_locator', function (Container $app) {
            return new AssetLocator(
                $app->make('zq_webpack.alias_manager'),
                config('zq_webpack.entry_file.path')
            );
        });
        $this->app->singleton('zq_webpack.asset_resolver', function (Container $app) {
            return new AssetResolver(
                $app->make('zq_webpack.asset_locator'),
                $app->make('zq_webpack.entry_file_manager')
            );
        });
        $this->app->singleton('zq_webpack.asset_manager', function (Container $app) {
            return new AssetManager(
                $app->make('zq_webpack.manifest_storage'),
                $app->make('zq_webpack.asset_name_generator'),
                $app->make('zq_webpack.entry_file_manager')
            );
        });

        return $this;
    }

    /**
     * @return $this
     */
    private function registerConfigs()
    {
        $this->app->singleton('zq_webpack.config_dumper', function (Container $app) {
            return new WebpackConfigDumper(
                $app->make('zq_webpack.storage'),
                config('zq_webpack.store.webpack_entry_config_path'),
                config('zq_webpack.webpack.config_path'),
                config('zq_webpack.store.json_manifest_file_path'),
                config('app.env'),
                config('zq_webpack.webpack.config_parameters')
            );
        });
        $this->app->singleton('zq_webpack.webpack_config_manager', function (Container $app) {
            return new WebpackConfigManager(
                $app->make('zq_webpack.alias_manager'),
                $app->make('zq_webpack.asset_collector'),
                $app->make('zq_webpack.config_dumper'),
                $app->make('zq_webpack.asset_resolver'),
                $app->make('zq_webpack.asset_name_generator'),
                $app->make('zq_webpack.error_handler')
            );
        });

        return $this;
    }

    /**
     * @return $this
     */
    private function registerCompiler()
    {
        $this->app->singleton('zq_webpack.manifest_storage', function (Container $app) {
            return new ManifestStorage(
                $app->make('zq_webpack.storage'),
                config('zq_webpack.store.manifest_file_path')
            );
        });
        $this->app->singleton('zq_webpack.webpack_process_builder', function (Container $app) {
            return new WebpackProcessBuilder(
                config('zq_webpack.bin.working_directory'),
                config('zq_webpack.bin.disable_tty'),
                config('zq_webpack.bin.webpack.executable'),
                config('zq_webpack.bin.webpack.arguments'),
                config('zq_webpack.bin.dev_server.executable'),
                config('zq_webpack.bin.dev_server.arguments'),
                config('zq_webpack.bin.dashboard.executable'),
                config('zq_webpack.bin.dashboard.mode')
            );
        });
        $this->app->singleton('zq_webpack.webpack_compiler', function (Container $app) {
            return new WebpackCompiler(
                $app->make('zq_webpack.storage'),
                $app->make('zq_webpack.webpack_config_manager'),
                config('zq_webpack.store.json_manifest_file_path'),
                $app->make('zq_webpack.manifest_storage'),
                $app->make('zq_webpack.webpack_process_builder'),
                $app->make('log')
            );
        });

        return $this;
    }

    /**
     * @return $this
     */
    private function registerProviders()
    {
        $this->app->singleton('zq_webpack.storage', function (Container $app) {
            $driver = new Local(storage_path('webpack'));

            return new Filesystem($driver);
        });
        $this->app->singleton('zq_webpack.asset_provider.twig_file', function (Container $app) {
            return new TwigAssetProvider($app->make('twig'), $app->make('zq_webpack.error_handler'));
        });
        $this->app->singleton('zq_webpack.directory_provider.configured', function (Container $app) {
            $paths = array_merge(config('view.paths'), config('zq_webpack.views.directories'));

            return new ConfiguredDirectoryProvider($paths);
        });
        $this->app->singleton('zq_webpack.asset_provider.blade', function (Container $app) {
            return new BladeProvider(
                $app->make('view')->getEngineResolver()->resolve('blade')->getCompiler(),
                $app->make('zq_webpack.helper'),
                $app->make('zq_webpack.error_handler')
            );
        });
        $this->app->singleton(BladeDirectoryAssetProvider::class, function (Container $app) {
            return new BladeDirectoryAssetProvider(
                $app->make('zq_webpack.asset_provider.blade'),
                $app->make('zq_webpack.directory_provider.configured')
            );
        });
        $this->app->singleton(TwigDirectoryAssetProvider::class, function (Container $app) {
            return new TwigDirectoryAssetProvider(
                $app->make('zq_webpack.asset_provider.twig_file'),
                '*.twig',
                $app->make('zq_webpack.directory_provider.configured')
            );
        });

        return $this;
    }

    /**
     * @return $this
     */
    private function registerErrorHandlers()
    {
        $this->app->singleton('zq_webpack.error_handler.default', function (Container $app) {
            return new DefaultErrorHandler();
        });
        $this->app->singleton('zq_webpack.error_handler.suppressing', function (Container $app) {
            return new SuppressingErrorHandler($app->make('log'));
        });
        $this->app->singleton('zq_webpack.error_handler.ignore_unknowns', function (Container $app) {
            return new UnknownReferenceIgnoringErrorHandler($app->make('log'));
        });

        $this->app->alias(config('zq_webpack.views.error_handler'), 'zq_webpack.error_handler');

        return $this;
    }

    /**
     * @return $this
     */
    private function registerCommands()
    {
        $this->app->singleton('zq_webpack.command.compile', function (Container $app) {
            return new CompileCommand(
                $app->make('log')
            );
        });

        $this->app->singleton('zq_webpack.command.dev_server', function (Container $app) {
            return new DevServerCommand();
        });

        $this->app->singleton('zq_webpack.command.setup', (function (Container $app) {
            return new SetupCommand(
                $this->resourcesPath('/configs/v1/package.json'),
                $this->resourcesPath('/configs/v1/webpack.config.js'),
                $this->resourcesPath('/configs/v2/package.json'),
                $this->resourcesPath('/configs/v2/webpack.config.js'),
                config('zq_webpack.bin.working_directory'),
                config('zq_webpack.webpack.config_path')
            );
        })->bindTo($this));

        $this->commands(['zq_webpack.command.compile', 'zq_webpack.command.dev_server', 'zq_webpack.command.setup']);

        return $this;
    }

    /**
     * @return $this
     */
    private function registerBladeExtension()
    {
        $this->app->singleton('zq_webpack.helper', function (Container $app) {
            return new WebpackHelper($app->make('zq_webpack.asset_manager'));
        });
        $this->app->alias('zq_webpack.helper', WebpackHelper::class);

        return $this;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function resourcesPath($path = null)
    {
        return dirname(__DIR__).'/resources'.($path ?: '');
    }
}
