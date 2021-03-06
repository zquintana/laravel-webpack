<?php

namespace ZQuintana\LaravelWebpack\Compiler;

use League\Flysystem\Filesystem;
use ZQuintana\LaravelWebpack\Config\WebpackConfig;
use ZQuintana\LaravelWebpack\Config\WebpackConfigManager;
use ZQuintana\LaravelWebpack\Exception\NoEntryPointsException;
use ZQuintana\LaravelWebpack\Service\ManifestStorage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Closure;
use RuntimeException;
use Exception;

/**
 * Class WebpackCompiler
 */
class WebpackCompiler
{
    /**
     * @var Filesystem
     */
    private $store;

    /**
     * @var WebpackConfigManager
     */
    private $webpackConfigManager;

    /**
     * @var string
     */
    private $manifestPath;

    /**
     * @var ManifestStorage
     */
    private $manifestStorage;

    /**
     * @var WebpackProcessBuilder
     */
    private $webpackProcessBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * WebpackCompiler constructor.
     * @param Filesystem            $store
     * @param WebpackConfigManager  $webpackConfigManager
     * @param string                $manifestPath
     * @param ManifestStorage       $manifestStorage
     * @param WebpackProcessBuilder $webpackProcessBuilder
     * @param LoggerInterface       $logger
     */
    public function __construct(
        Filesystem $store,
        WebpackConfigManager $webpackConfigManager,
        $manifestPath,
        ManifestStorage $manifestStorage,
        WebpackProcessBuilder $webpackProcessBuilder,
        LoggerInterface $logger
    ) {
        $this->store = $store;
        $this->webpackConfigManager = $webpackConfigManager;
        $this->manifestPath = $manifestPath;
        $this->manifestStorage = $manifestStorage;
        $this->webpackProcessBuilder = $webpackProcessBuilder;
        $this->logger = $logger;
    }

    /**
     * @param Closure|null       $callback
     * @param WebpackConfig|null $previousConfig
     */
    public function compile(Closure $callback = null, WebpackConfig $previousConfig = null)
    {
        // remove manifest file if exists - keep sure we create new one
        $this->removeManifestFile();

        try {
            $config = $this->webpackConfigManager->dump($previousConfig);
        } catch (NoEntryPointsException $exception) {
            $this->outputNoEntryPointsNotice($callback);
            return;
        }

        $process = $this->webpackProcessBuilder->buildWebpackProcess($config);

        $process->mustRun($callback);
        $this->saveManifest();
    }

    public function compileAndWatch(Closure $callback = null)
    {
        // remove manifest file if exists - keep sure we create new one
        $this->removeManifestFile();

        try {
            $config = $this->webpackConfigManager->dump();
        } catch (NoEntryPointsException $exception) {
            $this->outputNoEntryPointsNotice($callback);
            return;
        }

        $process = $this->webpackProcessBuilder->buildDevServerProcess($config);

        $that = $this;
        $logger = $this->logger;
        $processCallback = function ($type, $buffer) use ($that, $callback, $logger) {
            $that->saveManifest(false);
            $logger->info('Processing callback from process', array($type, $buffer));
            if ($callback !== null) {
                $callback($type, $buffer);
            }
        };

        $this->logger->info('Starting process', array($process->getCommandLine()));
        $process->start($processCallback);

        try {
            $this->loop($process, $config, $processCallback, $callback);
        } catch (Exception $exception) {
            $process->stop();
            throw $exception;
        }
    }

    private function loop(Process $process, WebpackConfig $previousConfig, $processCallback, $callback)
    {
        while (true) {
            sleep(1);
            $this->logger->debug('Dumping webpack configuration', array($process->getPid()));

            try {
                $config = $this->webpackConfigManager->dump($previousConfig);
            } catch (NoEntryPointsException $exception) {
                $process->stop();
                $this->outputNoEntryPointsNotice($callback);
                return;
            }

            if ($config->wasFileDumped()) {
                $this->logger->info(
                    'File was dumped (configuration changed) - restarting process',
                    $config->getEntryPoints()
                );
                $process->stop();
                $process = $process->restart($processCallback);
                $previousConfig = $config;
            } else {
                if (!$process->isRunning()) {
                    $this->logger->info('Process has shut down - returning');
                    return;
                }
                $process->getOutput();

                // try to save the manifest - output callback is not called in dashboard mode
                $this->saveManifest(false);
            }
        }
    }

    public function saveManifest($failIfMissing = true)
    {
        if (!$this->store->has($this->manifestPath)) {
            if ($failIfMissing) {
                throw new RuntimeException(
                    'Missing manifest file in ' . $this->manifestPath
                    . '. Keep sure assets-webpack-plugin is enabled with the same path in webpack config'
                );
            }
            return;
        }

        $manifest = json_decode($this->store->read($this->manifestPath), true);
        $this->manifestStorage->saveManifest($manifest);

        if (!$this->store->delete($this->manifestPath)) {
            throw new RuntimeException('Cannot unlink manifest file at ' . $this->manifestPath);
        }
    }

    private function removeManifestFile()
    {
        if ($this->store->has($this->manifestPath)) {
            $this->logger->info('Deleting manifest file', array($this->manifestPath));
            $this->store->delete($this->manifestPath);
        }
    }

    private function outputNoEntryPointsNotice(Closure $callback = null)
    {
        if ($callback !== null) {
            $callback(Process::OUT, 'No entry points found - not running webpack' . PHP_EOL);
        }
    }
}
