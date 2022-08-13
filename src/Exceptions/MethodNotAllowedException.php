<?php

declare(strict_types=1);

namespace JsonServer\Exceptions;

class MethodNotAllowedException extends HttpException
{
    public function __construct()
    {
        parent::__construct('Method Not Allowed', 405);
    }
}
