<?php

declare(strict_types=1);

namespace JsonServer\Exceptions;

class EmptyBodyException extends HttpException
{
    public function __construct()
    {
        parent::__construct('Empty Body', 400);
    }
}
