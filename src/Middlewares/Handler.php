<?php

declare(strict_types=1);

namespace JsonServer\Middlewares;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Handler
{
    public function __construct(
        private \closure $execute
    ) {
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        return ($this->execute)($request);
    }
}
