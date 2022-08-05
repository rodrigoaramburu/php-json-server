<?php

declare(strict_types=1);

namespace JsonServer\Middlewares;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Middleware
{
    private ?Middleware $next = null;

    public function setNext(Middleware $next): void
    {
        $this->next = $next;
    }

    public function next(): Middleware
    {
        return $this->next;
    }

    public function handle(RequestInterface $request, \closure $serverProcess): ResponseInterface
    {
        $handler = new Handler(function ($request) use ($serverProcess) {
            if ($this->next !== null) {
                return $this->next->handle($request, $serverProcess);
            }

            return $serverProcess($request);
        });

        return $this->process($request, $handler);
    }

    abstract public function process(RequestInterface $request, Handler $handler): ResponseInterface;
}
