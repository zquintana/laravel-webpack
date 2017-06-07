<?php

namespace ZQuintana\LaravelWebpack\AssetProvider;

use Symfony\Component\Finder\Finder;
use ZQuintana\LaravelWebpack\AssetProvider\DirectoryProvider\DirectoryProviderInterface;

/**
 * Class BladeDirectoryAssetProvider
 */
class BladeDirectoryAssetProvider implements AssetProviderInterface
{
    /**
     * @var BladeProvider
     */
    private $bladeProvider;

    /**
     * @var DirectoryProviderInterface
     */
    private $directoryProvider;

    /**
     * @var string
     */
    private $pattern;


    /**
     * BladeDirectoryAssetProvider constructor.
     *
     * @param BladeProvider              $bladeProvider
     * @param DirectoryProviderInterface $directoryProvider
     * @param string                     $pattern
     */
    public function __construct(
        BladeProvider $bladeProvider,
        DirectoryProviderInterface $directoryProvider,
        $pattern = '*.blade.php'
    ) {
        $this->bladeProvider     = $bladeProvider;
        $this->directoryProvider = $directoryProvider;
        $this->pattern           = $pattern;
    }

    /**
     * @param array $previousContext
     *
     * @return AssetResult
     */
    public function getAssets($previousContext = null)
    {
        $resources = [];
        foreach ($this->directoryProvider->getDirectories() as $directory) {
            foreach ($this->createFinder($directory) as $file) {
                $resources[] = $file->getRealPath();
            }
        }

        $result  = new AssetResult();
        $context = [];
        foreach ($resources as $file) {
            $assetResult = $this->bladeProvider->getAssets(
                $file,
                isset($previousContext[$file]) ? $previousContext[$file] : null
            );
            $context[$file] = $assetResult->getContext();
            $result->addAssets($assetResult->getAssets());
        }
        $result->setContext($context);

        return $result;
    }

    /**
     * @param string $resource
     *
     * @return array|Finder
     */
    private function createFinder($resource)
    {
        if (!is_dir($resource)) {
            return [];
        }

        $finder = new Finder();
        $finder->in($resource)->followLinks()->files()->name($this->pattern);

        return $finder;
    }
}
