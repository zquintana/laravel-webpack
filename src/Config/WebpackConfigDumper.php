<?php

namespace ZQuintana\LaravelWebpack\Config;

use League\Flysystem\Filesystem;

/**
 * Class WebpackConfigDumper
 */
class WebpackConfigDumper
{
    /**
     * @var Filesystem
     */
    private $store;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $includeConfigPath;

    /**
     * @var string
     */
    private $manifestPath;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var array
     */
    private $parameters;


    /**
     * @param Filesystem $store
     * @param string     $path              full path where config should be dumped
     * @param string     $includeConfigPath path of config to be included inside dumped config
     * @param string     $manifestPath
     * @param string     $environment
     * @param array      $parameters
     */
    public function __construct(
        Filesystem $store,
        $path,
        $includeConfigPath,
        $manifestPath,
        $environment,
        array $parameters
    ) {
        $this->store = $store;
        $this->path  = $path;
        $this->includeConfigPath = $includeConfigPath;
        $this->manifestPath = $manifestPath;
        $this->environment  = $environment;
        $this->parameters   = $parameters;
    }

    /**
     * @param WebpackConfig $config
     * @return string
     */
    public function dump(WebpackConfig $config)
    {
        $configTemplate = 'module.exports = require(%s)(%s);';
        $configContents = sprintf(
            $configTemplate,
            json_encode($this->includeConfigPath),
            json_encode(array(
                'entry' => (object)$config->getEntryPoints(),
                'groups' => (object)$config->getAssetGroups(),
                'alias' => (object)$config->getAliases(),
                'manifest_path' => storage_path('webpack/'.$this->manifestPath),
                'environment' => $this->environment,
                'parameters' => (object)$this->parameters,
            ))
        );

        $this->store->put($this->path, $configContents);

        return storage_path('webpack/'.$this->path);
    }
}
