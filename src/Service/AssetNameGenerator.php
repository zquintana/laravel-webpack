<?php

namespace ZQuintana\LaravelWebpack\Service;

class AssetNameGenerator
{
    public function generateName($asset)
    {
        return sprintf('%s-%s', pathinfo($asset, PATHINFO_FILENAME), sha1($asset));
    }
}
