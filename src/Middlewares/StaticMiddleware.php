<?php

declare(strict_types=1);

namespace JsonServer\Middlewares;

use Exception;
use JsonServer\Utils\JsonFile;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StaticMiddleware extends Middleware
{
    private array $routes = [];
    private Psr17Factory $psr17Factory;
    private string $pathBody;

    public function __construct(string|array $routes = 'static.json')
    {
        $this->psr17Factory = new Psr17Factory();

        if (is_string($routes)) {
            $jsonFile = new JsonFile('r+b');
            $this->routes = $jsonFile->loadFile($routes);
            $this->pathBody = dirname($routes);
            return;
        }        
        $this->routes = $routes;
    }

    public function process(ServerRequestInterface $request, Handler $handler): ResponseInterface
    {
        $route = $this->getRoute($request);
        if ($route !== null) {
            return $this->response($route);
        }

        return $handler->handle($request);
    }

    private function getRoute(ServerRequestInterface $request): ?array
    {
        if (! array_key_exists($request->getUri()->getPath(), $this->routes)) {
            return null;
        }
        $route = $this->routes[$request->getUri()->getPath()];

        if (array_key_exists($request->getMethod(), $route)) {
            return $route[$request->getMethod()];
        }
    }

    private function response(array $route): ResponseInterface
    {
        $response = $this->psr17Factory->createResponse(intval($route['statusCode']) ?? 200)
                        ->withBody($this->psr17Factory->createStream($route['body'] ?? ''));


        $response = $this->addBodyFromFileIfExist($route['body-file'] ?? null, $response);

        $response = $this->addHeaderToResponse($route['headers'] ?? [], $response);

        return $response;
    }


    private function addHeaderToResponse(array $headers, ResponseInterface $response): ResponseInterface
    {
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        } 
        return $response; 
    }

    private function addBodyFromFileIfExist(?string $bodyFile, ResponseInterface $response): ResponseInterface
    {
        if ($bodyFile === null) {
            return $response;
        }

        $file = $this->pathBody.'/'.$bodyFile;
        if (! file_exists($file)) {
            throw new Exception('file not found: '.$file);
        }

        return $response->withBody(
            $this->psr17Factory->createStream(file_get_contents($file) ?? '')
        );
        
        
    }
}
