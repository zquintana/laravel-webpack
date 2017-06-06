<?php

namespace ZQuintana\LaravelWebpack\AssetProvider;

use ZQuintana\LaravelWebpack\Exception\InvalidContextException;
use ZQuintana\LaravelWebpack\Exception\InvalidResourceException;

/**
 * @api
 */
interface AssetProviderInterface
{
    /**
     * @param mixed|null $previousContext
     * @return AssetResult
     *
     * @throws InvalidResourceException
     * @throws InvalidContextException
     */
    public function getAssets($previousContext = null);
}
