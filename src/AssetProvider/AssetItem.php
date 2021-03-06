<?php

namespace ZQuintana\LaravelWebpack\AssetProvider;

/**
 * @api
 */
class AssetItem
{
    /**
     * @var string
     */
    private $resource;

    /**
     * @var string|null
     */
    private $group;


    /**
     * AssetItem constructor.
     *
     * @param string $resource
     * @param string $group
     */
    public function __construct($resource = null, $group = null)
    {
        $this->resource = $resource;
        $this->group    = $group;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return null|string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param null|string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }
}
