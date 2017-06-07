<?php

namespace ZQuintana\LaravelWebpack\Service;

use League\Flysystem\Filesystem;
use RuntimeException;

/**
 * Class ManifestStorage
 */
class ManifestStorage
{
    /**
     * @var Filesystem
     */
    private $store;

    /**
     * @var string
     */
    private $manifestPath;


    /**
     * ManifestStorage constructor.
     *
     * @param Filesystem $store
     * @param string     $manifestPath
     */
    public function __construct(Filesystem $store, $manifestPath)
    {
        $this->store        = $store;
        $this->manifestPath = $manifestPath;
    }

    /**
     * @param array $manifest
     */
    public function saveManifest(array $manifest)
    {
        $this->store->put($this->manifestPath, '<?php return ' . var_export($manifest, true).';');
    }

    /**
     * @return mixed
     */
    public function getManifest()
    {
        if (!$this->store->has($this->manifestPath)) {
            throw new RuntimeException(sprintf(
                'Manifest file not found: %s. %s',
                $this->manifestPath,
                'You must run zq:webpack:compile or zq:webpack:dev-server before twig can render webpack assets'
            ));
        }

        return include storage_path('webpack/'.$this->manifestPath);
    }
}
