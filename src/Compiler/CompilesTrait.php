<?php

namespace ZQuintana\LaravelWebpack\Compiler;

/**
 * Trait CompilesTrait
 */
trait CompilesTrait
{
    /**
     * @return \Illuminate\Foundation\Application|mixed|WebpackCompiler
     */
    public function getCompiler()
    {
        return app('zq_webpack.webpack_compiler');
    }
}
