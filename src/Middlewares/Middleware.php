<?php

declare(strict_types=1);

namespace JsonServer\Middlewares;

use JsonServer\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Middleware
{
    private ?Middleware $next = null;

    private Database $database;

    private array $config;

    public function setNext(Middleware $next): void
    {
        $this->next = $next;
    }

    public function next(): Middleware
    {
        return $this->next;
    }

    public function handle(ServerRequestInterface $request, \closure $serverProcess): ResponseInterface
    {
        $handler = new Handler(function ($request) use ($serverProcess) {
            if ($this->next !== null) {
                return $this->next->handle($request, $serverProcess);
            }

            return $serverProcess($request);
        });

        return $this->process($request, $handler);
    }

    protected function database(): Database
    {
        return $this->database;
    }

    protected function config(): array
    {
        return $this->config;
    }

    public function setDatabase(Database $database): void
    {
        $this->database = $database;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    abstract public function process(ServerRequestInterface $request, Handler $handler): ResponseInterface;
}
