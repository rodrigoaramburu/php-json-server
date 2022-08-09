<?php

declare(strict_types=1);

namespace JsonServer\Middlewares;

use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class StaticMiddleware extends Middleware
{
    private array $routes = [];

    private string $pathBody;

    public function __construct(string|array $routes = 'static.json')
    {
        if (is_string($routes)) {
            if (! file_exists($routes)) {
                throw new Exception('cannot open file '.$routes);
            }
            $r = json_decode(file_get_contents($routes), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('cannot read file '.$routes);
            }
            $this->routes = $r;
            $this->pathBody = dirname($routes);
        }
        if (is_array($routes)) {
            $this->routes = $routes;
        }
    }

    public function process(RequestInterface $request, Handler $handler): ResponseInterface
    {
        $route = $this->getRoute($request);
        if ($route !== null) {
            return $this->response($route);
        }

        return $handler->handle($request);
    }

    private function getRoute(RequestInterface $request): ?array
    {
        if (! array_key_exists($request->getUri()->getPath(), $this->routes)) {
            return null;
        }
        $route = $this->routes[$request->getUri()->getPath()];

        if (array_key_exists($request->getMethod(), $route)) {
            return $route[$request->getMethod()];
        }

        return null;
    }

    private function response(array $route): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();

        $response = $psr17Factory->createResponse($route['statusCode'] ?? 200)
                        ->withBody($psr17Factory->createStream($route['body'] ?? ''));

        if (array_key_exists('body-file', $route)) {
            $file = $this->pathBody.'/'.$route['body-file'];
            if (! file_exists($this->pathBody.'/'.$route['body-file'])) {
                throw new Exception('file not found: '.$this->pathBody.'/'.$route['body-file']);
            }

            $response = $response->withBody(
                $psr17Factory->createStream(file_get_contents($this->pathBody.'/'.$route['body-file']) ?? '')
            );
        }

        foreach ($route['headers'] ?? [] as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }
}
