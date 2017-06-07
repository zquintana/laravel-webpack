<?php

namespace ZQuintana\LaravelWebpack\Service;

use RuntimeException;

/**
 * Class AliasManager
 */
class AliasManager
{
    private $additionalAliases;

    /**
     * @var null|array
     */
    private $aliases = null;

    /**
     * @param array $additionalAliases
     */
    public function __construct(array $additionalAliases)
    {
        $this->additionalAliases = $additionalAliases;
    }

    /**
     * @return array|null
     */
    public function getAliases()
    {
        if ($this->aliases !== null) {
            return $this->aliases;
        }

        $aliases = array();

        // give priority to additional to be able to overwrite bundle aliases
        foreach ($this->additionalAliases as $alias => $path) {
            $realPath = realpath($path);
            if ($realPath === false) {
                // just skip - allow non-existing aliases, like default ones
                unset($aliases['@'.$alias]);

                continue;
            }
            $aliases['@'.$alias] = $realPath;
        }

        $this->aliases = $aliases;

        return $aliases;
    }

    /**
     * @param string $alias
     *
     * @return mixed
     */
    public function getAliasPath($alias)
    {
        $aliases = $this->getAliases();
        if (!isset($aliases[$alias])) {
            throw new RuntimeException(sprintf('Alias not registered: %s', $alias));
        }

        return $aliases[$alias];
    }
}
