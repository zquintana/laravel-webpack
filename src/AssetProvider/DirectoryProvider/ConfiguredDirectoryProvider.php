<?php

namespace ZQuintana\LaravelWebpack\AssetProvider\DirectoryProvider;

/**
 * Class ConfiguredDirectoryProvider
 */
class ConfiguredDirectoryProvider implements DirectoryProviderInterface
{
    private $directories;

    public function __construct(array $directories)
    {
        $this->directories = $directories;
    }

    public function getDirectories()
    {
        return $this->directories;
    }
}
