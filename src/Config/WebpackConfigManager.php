<?php

namespace ZQuintana\LaravelWebpack\Config;

use ZQuintana\LaravelWebpack\ErrorHandler\ErrorHandlerInterface;
use ZQuintana\LaravelWebpack\Exception\AssetNotFoundException;
use ZQuintana\LaravelWebpack\Exception\NoEntryPointsException;
use ZQuintana\LaravelWebpack\Service\AliasManager;
use ZQuintana\LaravelWebpack\Service\AssetCollector;
use ZQuintana\LaravelWebpack\Service\AssetNameGenerator;
use ZQuintana\LaravelWebpack\Service\AssetResolver;

class WebpackConfigManager
{
    const DEFAULT_GROUP_NAME = 'default';

    private $aliasManager;
    private $assetCollector;
    private $configDumper;
    private $assetResolver;
    private $assetNameGenerator;
    private $errorHandler;

    public function __construct(
        AliasManager $aliasManager,
        AssetCollector $assetCollector,
        WebpackConfigDumper $configDumper,
        AssetResolver $assetResolver,
        AssetNameGenerator $assetNameGenerator,
        ErrorHandlerInterface $errorHandler
    ) {
        $this->aliasManager = $aliasManager;
        $this->assetCollector = $assetCollector;
        $this->configDumper = $configDumper;
        $this->assetResolver = $assetResolver;
        $this->assetNameGenerator = $assetNameGenerator;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param WebpackConfig $previousConfig
     * @return WebpackConfig
     */
    public function dump(WebpackConfig $previousConfig = null)
    {
        $aliases = $this->aliasManager->getAliases();
        $assetResult = $this->assetCollector->getAssets(
            $previousConfig !== null ? $previousConfig->getCacheContext() : null
        );
        $entryPoints = array();
        $assetGroups = array();
        foreach ($assetResult->getAssets() as $asset) {
            $assetName = $this->assetNameGenerator->generateName($asset->getResource());
            try {
                $entryPoints[$assetName] = $this->assetResolver->resolveAsset($asset->getResource());
            } catch (AssetNotFoundException $exception) {
                $this->errorHandler->processException($exception);
            }

            $groupName = $asset->getGroup() !== null ? $asset->getGroup() : self::DEFAULT_GROUP_NAME;
            $assetGroups[$groupName][] = $assetName;
        }

        if (count($entryPoints) === 0) {
            throw new NoEntryPointsException();
        }

        $config = new WebpackConfig();
        $config->setAliases($aliases);
        $config->setAssetGroups($assetGroups);
        $config->setEntryPoints($entryPoints);
        $config->setCacheContext($assetResult->getContext());

        if (
            $previousConfig === null
            || $aliases !== $previousConfig->getAliases()
            || $assetGroups !== $previousConfig->getAssetGroups()
            || $entryPoints !== $previousConfig->getEntryPoints()
            || !file_exists($previousConfig->getConfigPath())
        ) {
            $config->setConfigPath($this->configDumper->dump($config));
            $config->setFileDumped(true);
        } else {
            $config->setConfigPath($previousConfig->getConfigPath());
        }

        return $config;
    }
}
