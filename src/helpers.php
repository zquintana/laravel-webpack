<?php

if (!function_exists('webpack')) {
    /**
     * @return \ZQuintana\LaravelWebpack\Blade\WebpackHelper
     */
    function webpack()
    {
        return app('zq_webpack.helper');
    }
}

if (!function_exists('webpack_asset')) {
    /**
     * @param string $resource
     * @param string $type
     *
     * @return null|string
     */
    function webpack_asset($resource, $type = null)
    {
        return webpack()->asset($resource, $type);
    }
}

if (!function_exists('webpack_named_asset')) {
    /**
     * @param string $name
     * @param string $type
     *
     * @return null|string
     */
    function webpack_named_asset($name, $type = null)
    {
        return webpack()->namedAsset($name, $type);
    }
}

