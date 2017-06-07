<?php

namespace ZQuintana\LaravelWebpack\Service;

use ZQuintana\LaravelWebpack\Exception\AssetNotFoundException;
use RuntimeException;

/**
 * Class AssetLocator
 */
class AssetLocator
{
    /**
     * @var AliasManager
     */
    private $aliasManager;

    /**
     * @var string
     */
    private $assetsPath;


    /**
     * AssetLocator constructor.
     * @param AliasManager $aliasManager
     * @param string       $assetsPath
     */
    public function __construct(AliasManager $aliasManager, $assetsPath)
    {
        $this->aliasManager = $aliasManager;
        $this->assetsPath   = $assetsPath;
    }

    /**
     * Locates asset - resolves alias if provided. Does not support assets with loaders.
     *
     * @param string $asset path of an asset, possibly with alias prefix
     * @return string resolved asset path
     * @throws AssetNotFoundException if asset was not found
     *
     * @api
     */
    public function locateAsset($asset)
    {
        if (substr($asset, 0, 1) === '@') {
            $locatedAsset = $this->resolveAlias($asset);
        } else {
            $locatedAsset = $this->assetsPath.DIRECTORY_SEPARATOR.$asset;
        }

        if (!file_exists($locatedAsset)) {
            throw new AssetNotFoundException(sprintf('Asset not found (%s, resolved to %s)', $asset, $locatedAsset));
        }

        return $locatedAsset;
    }

    private function resolveAlias($asset)
    {
        $position = mb_strpos($asset, '/');
        if ($position === false) {
            $position = mb_strlen($asset);
        }
        $alias = mb_substr($asset, 0, $position);
        try {
            $aliasPath = $this->aliasManager->getAliasPath($alias);
        } catch (RuntimeException $exception) {
            throw new AssetNotFoundException(
                sprintf('Cannot locate asset (%s) due to invalid alias (%s)', $asset, $alias),
                0,
                $exception
            );
        }
        return $aliasPath . mb_substr($asset, $position);
    }
}
