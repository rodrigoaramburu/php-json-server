<?php

declare(strict_types=1);

namespace JsonServer\Middlewares;

use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    public function process(string $method, string $uri, ?string $body, $handler): ResponseInterface;
}
