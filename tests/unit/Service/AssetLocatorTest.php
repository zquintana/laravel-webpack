<?php

namespace ZQuintana\LaravelWebpack\Tests\Service;

use Codeception\TestCase\Test;
use ZQuintana\LaravelWebpack\Exception\AssetNotFoundException;
use ZQuintana\LaravelWebpack\Service\AliasManager;
use ZQuintana\LaravelWebpack\Service\AssetLocator;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Exception;
use RuntimeException;

class AssetLocatorTest extends Test
{
    /**
     * @param string|Exception $expected
     * @param string $asset
     * @param string|null $expectedAlias
     * @param string|null|Exception $aliasPath
     * @dataProvider locateAssetProvider
     */
    public function testLocateAsset($expected, $asset, $expectedAlias = null, $aliasPath = null)
    {
        /** @var MockObject|AliasManager $aliasManager */
        $aliasManager = $this->getMockBuilder('ZQuintana\LaravelWebpack\Service\AliasManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        if ($expectedAlias !== null) {
            $expectation = $aliasManager->expects($this->once())->method('getAliasPath')->with($expectedAlias);
            if ($aliasPath instanceof Exception) {
                $expectation->willThrowException($aliasPath);
            } else {
                $expectation->willReturn($aliasPath);
            }
        } else {
            $aliasManager->expects($this->never())->method('getAliasPath');
        }

        $assetLocator = new AssetLocator($aliasManager);

        if ($expected instanceof Exception) {
            $this->setExpectedException(get_class($expected));
            $assetLocator->locateAsset($asset);
        } else {
            $this->assertSame($expected, $assetLocator->locateAsset($asset));
        }
    }

    public function locateAssetProvider()
    {
        $dir = realpath(__DIR__ . '/../Fixtures');
        return array(
            'works with full path' => array($dir . '/assetA.txt', $dir . '/assetA.txt'),
            'works with alias' => array($dir . '/assetA.txt', '@aliasName/assetA.txt', '@aliasName', $dir),
            'works with alias and subdirectories' => array(
                $dir . '/subdirectory/assetB.txt',
                '@aliasName/subdirectory/assetB.txt',
                '@aliasName',
                $dir,
            ),
            'throws exception if file not found' => array(
                new AssetNotFoundException(),
                $dir . '/non-existent-file',
            ),
            'throws exception if file not found via alias' => array(
                new AssetNotFoundException(),
                '@aliasName/subdirectory/does-not-exists',
                '@aliasName',
                $dir,
            ),
            'throws exception if alias not found' => array(
                new AssetNotFoundException(),
                '@aliasName/assetA.txt',
                '@aliasName',
                new RuntimeException(),
            ),
        );
    }
}
