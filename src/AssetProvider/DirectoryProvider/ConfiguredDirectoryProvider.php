<?php

namespace ZQuintana\LaravelWebpack\AssetProvider\DirectoryProvider;

/**
 * Class ConfiguredDirectoryProvider
 */
class ConfiguredDirectoryProvider implements DirectoryProviderInterface
{
    /**
     * @var array
     */
    private $directories;


    /**
     * ConfiguredDirectoryProvider constructor.
     *
     * @param array $directories
     */
    public function __construct(array $directories)
    {
        $this->directories = $directories;
    }

    /**
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
    }
}
