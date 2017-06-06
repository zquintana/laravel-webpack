<?php

namespace ZQuintana\LaravelWebpack\ErrorHandler;

use Exception;

/**
 * @api
 */
interface ErrorHandlerInterface
{
    public function processException(Exception $exception);
}
