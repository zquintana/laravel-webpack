<?php

return [
    'store' => [
        'manifest_file_path'        => 'manifest.php',
        'webpack_entry_config_path' => 'webpack.config.js',
        'json_manifest_file_path'   => 'webpack_manifest.json',
    ],

    'asset_providers' => [
        ZQuintana\LaravelWebpack\AssetProvider\BladeDirectoryAssetProvider::class,
    ],

    /**
     * Bin paths
     */
    'bin' => [
        'working_directory' => base_path(),
        'disable_tty' => true !== config('app.debug'),
        'webpack' => [
            'executable' => ['node_modules/.bin/webpack'],
            'arguments'  => [],
        ],
        'dev_server' => [
            'executable' => ['node_modules/.bin/webpack-dev-server'],
            'arguments'  => [],
        ],
        'dashboard' => [
            'executable' => ['node_modules/.bin/webpack-dashboard'],
            'mode'       => \ZQuintana\LaravelWebpack\Compiler\WebpackProcessBuilder::DASHBOARD_MODE_ENABLED_ON_DEV_SERVER,
        ],
    ],

    /**
     * Aliases
     */
    'aliases' => [],

    /**
     * App webpack config path
     */
    'webpack' => [
        'config_path' => config_path('webpack.config.js'),
        'config_parameters' => [],
    ],

    /**
     * View settings
     */
    'views' => [
        'directories' => [],
        'error_handler' => config('app.debug', false) ?
            'zq_webpack.error_handler.suppressing' : 'zq_webpack.error_handler.ignore_unknowns',
    ],

    /**
     * Entry file types
     */
    'entry_file' => [
        'path' => resource_path('js'),
        'enabled_extensions' => [],
        'disabled_extensions' => explode(',', 'js,jsx,ts,coffee,es6,ls'),
        'type_map' => [
            'css' => explode(',', 'less,scss,sass,styl'),
        ],
    ],
];
