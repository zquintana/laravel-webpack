<?php

namespace ZQuintana\LaravelWebpack\AssetProvider;

use Illuminate\View\Compilers\BladeCompiler;
use ZQuintana\LaravelWebpack\Blade\AssetGatherer;
use ZQuintana\LaravelWebpack\Blade\WebpackHelper;
use ZQuintana\LaravelWebpack\ErrorHandler\ErrorHandlerInterface;
use ZQuintana\LaravelWebpack\Exception\InvalidContextException;
use ZQuintana\LaravelWebpack\Exception\InvalidResourceException;
use ZQuintana\LaravelWebpack\Exception\ResourceParsingException;

/**
 * Class BladeProvider
 */
class BladeProvider
{
    const T_STRING                   = 'T_STRING';
    const T_CONSTANT_ENCAPSED_STRING = 'T_CONSTANT_ENCAPSED_STRING';

    /**
     * @var BladeCompiler
     */
    private $compiler;

    /**
     * @var WebpackHelper
     */
    private $helper;

    /**
     * @var ErrorHandlerInterface
     */
    private $errorHandler;

    /**
     * @var array
     */
    private $validFunctions = [
        'webpack_asset', 'webpack_named_asset',
    ];

    /**
     * @var array
     */
    private $validMethod = [
        'asset', 'namedAsset',
    ];

    /**
     * @var array
     */
    private $validFacade = [
        'Webpack',
    ];


    /**
     * BladeProvider constructor.
     *
     * @param BladeCompiler         $compiler
     * @param WebpackHelper         $helper
     * @param ErrorHandlerInterface $errorHandler
     */
    public function __construct(
        BladeCompiler $compiler,
        WebpackHelper $helper,
        ErrorHandlerInterface $errorHandler
    ) {
        $this->compiler     = $compiler;
        $this->helper       = $helper;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param string $file
     * @param array  $previousContext
     *
     * @return AssetResult
     */
    public function getAssets($file, $previousContext = null)
    {
        if (!is_string($file)) {
            throw new InvalidResourceException('Expected string filename as resource', $file);
        } elseif (!is_file($file) || !is_readable($file) || !stream_is_local($file)) {
            throw new InvalidResourceException('File not found, not readable or not local', $file);
        }

        if ($previousContext !== null) {
            if (!is_array($previousContext)
                || !isset($previousContext['modified_at'])
                || !is_int($previousContext['modified_at'])
                || !isset($previousContext['assets'])
                || !is_array($previousContext['assets'])
            ) {
                throw new InvalidContextException(
                    'Expected context with int `modified_at` and array `assets`',
                    $previousContext
                );
            }

            if ($previousContext['modified_at'] === filemtime($file)) {
                $assetResult = new AssetResult();
                $assetResult->setAssets($previousContext['assets']);
                $assetResult->setContext($previousContext);

                return $assetResult;
            }
        }

        try {
            $result = $this->parse($file);
            $result->setContext(array('modified_at' => filemtime($file)));

            return $result;
        } catch (ResourceParsingException $e) {
            $this->errorHandler->processException($e);

            return new AssetResult();
        }
    }

    /**
     * @param string $file
     *
     * @return AssetResult
     */
    private function parse($file)
    {
        $compiled = $this->compiler->compileString('?>'.file_get_contents($file).'<?php');

        $result = new AssetResult();
        $tokens = token_get_all($compiled);
        while ($token = array_shift($tokens)) {
            $tokenName = is_array($token) ? token_name($token[0]) : null;
            if ($tokenName !== self::T_STRING) {
                continue;
            }

            if (in_array($token[1], $this->validFunctions)) {
                $result->addAsset($this->parseFunction($tokens));
            } elseif (in_array($token[1], $this->validFacade)) {
                $asset = $this->parseFacade($file, $token, $tokens);
                if (!$asset) {
                    continue;
                }

                $result->addAsset($asset);
            }
        }

        return $result;
    }

    /**
     * @param string $file
     * @param array  $currentToken
     * @param array  $tokens
     *
     * @return null|AssetItem
     */
    private function parseFacade($file, $currentToken, array &$tokens)
    {
        $next = array_shift($tokens);
        if (!is_array($next)) {
            throw new ResourceParsingException(sprintf(
                'Expecting namespace on line %s of %s after %s',
                $currentToken[2],
                $file,
                $currentToken[1]
            ));
        }

        $next = array_shift($tokens);
        $tokenName = token_name($next[0]);
        if (!is_array($next) || $tokenName !== self::T_STRING) {
            throw new ResourceParsingException(sprintf(
                'Excepting method on line %s of %s after %s',
                $next[2],
                $file,
                $next[1]
            ));
        }

        if (!in_array($next[1], $this->validMethod)) {
            return null;
        }

        return $this->parseFunction($tokens);
    }

    /**
     * @param array $tokens
     *
     * @return AssetItem
     */
    private function parseFunction(array &$tokens)
    {
        array_shift($tokens);
        $token = array_shift($tokens);
        $name = token_name($token[0]);

        if ($name !== self::T_CONSTANT_ENCAPSED_STRING) {
            throw new ResourceParsingException('Expecting string asset with Blade templates');
        }

        return new AssetItem(trim($token[1], '"\''));
    }
}
