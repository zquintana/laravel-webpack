<?php

namespace ZQuintana\LaravelWebpack\Blade;

use ZQuintana\LaravelWebpack\Service\AssetManager;

/**
 * Class WebpackHelper
 */
class WebpackHelper
{
    /**
     * @var AssetManager
     */
    private $manager;


    /**
     * WebpackHelper constructor.
     *
     * @param AssetManager $manager
     */
    public function __construct(AssetManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string $resource
     * @param string $type
     *
     * @return null|string
     */
    public function asset($resource, $type = null)
    {
        return $this->manager->getAssetUrl($resource, $type);
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return null|string
     */
    public function namedAsset($name, $type = null)
    {
        return $this->manager->getNamedAssetUrl($name, $type);
    }
}
