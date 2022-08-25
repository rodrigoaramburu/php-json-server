<?php

declare(strict_types=1);

namespace JsonServer\Exceptions;

class NotFoundResourceRepositoryException extends HttpException
{
    public function __construct()
    {
        parent::__construct('Not Found', 404);
    }
}
