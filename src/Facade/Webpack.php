<?php

namespace ZQuintana\LaravelWebpack\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class Webpack
 */
class Webpack extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'zq_webpack.helper';
    }
}
