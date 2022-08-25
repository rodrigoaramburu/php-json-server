<?php

declare(strict_types=1);

namespace JsonServer;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use JsonServer\Exceptions\HttpException;
use JsonServer\Exceptions\MethodNotAllowedException;
use JsonServer\Method\Delete;
use JsonServer\Method\Get;
use JsonServer\Method\HttpMethod;
use JsonServer\Method\Post;
use JsonServer\Method\Put;
use JsonServer\Middlewares\Middleware;
use JsonServer\Utils\ParsedUri;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Server
{
    private ?Middleware $middleware = null;

    private array $config = [];

    public function __construct(array|string $config = [])
    {
        $this->loadConfig($config);

        $this->database = new Database(databasefile: $this->config['database-file']);
        $this->psr17Factory = new Psr17Factory();
        $this->inflector = InflectorFactory::create()->build();
    }

    public function handle(string $method, string $uri, ?string $body = null, ?array $headers = []): ResponseInterface
    {
        $query = parse_url($uri);
        parse_str($query['query'] ?? '', $query);

        $request = $this->psr17Factory
                            ->createServerRequest($method, $uri)
                            ->withQueryParams($query)
                            ->withBody($this->psr17Factory->createStream($body ?? ''));

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        $response = $this->psr17Factory
                                ->createResponse(200)
                                ->withHeader('Content-type', 'application/json')
                                ->withHeader('Access-Control-Allow-Origin', '*')
                                ->withHeader('Access-Control-Allow-Headers', '*')
                                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        if ($this->middleware !== null) {
            return $this->middleware->handle($request, function ($request) use ($response) {
                return $this->process($request, $response);
            });
        }

        return $this->process($request, $response);
    }

    public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsedUri = ParsedUri::parseUri($request->getUri()->getPath());

        try {
            $httpMethodHandler = $this->httpMethodHandler($request->getMethod());

            return $httpMethodHandler->execute($request, $response, $parsedUri);
        } catch (HttpException $e) {
            $bodyResponse = $this->psr17Factory->createStream(json_encode([
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ]));

            return $response
                ->withStatus($e->getCode())
                ->withBody($bodyResponse);
        }
    }

    public function httpMethodHandler(string $httpMethod): HttpMethod
    {
        $httpMehtodHandler = match (strtoupper($httpMethod)) {
            'GET' => new Get($this),
            'POST' => new Post($this),
            'PUT' => new Put($this),
            'DELETE' => new Delete($this),
            default => throw new MethodNotAllowedException()
        };

        return $httpMehtodHandler;
    }

    public function send(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $key => $value) {
            header("$key: {$value[0]}");
        }
        echo (string) $response->getBody();
    }

    public function addMiddleware(Middleware $middleware): self
    {
        $middleware->setDatabase($this->database);
        $middleware->setConfig($this->config);

        if ($this->middleware === null) {
            $this->middleware = $middleware;

            return $this;
        }

        $middleware->setNext($this->middleware);
        $this->middleware = $middleware;

        return $this;
    }

    private function loadConfig(array|string $config): void
    {
        if (is_array($config)) {
            $this->config = $config;
        }

        $this->config = array_merge([
            'database-file' => getcwd().'/db.json',
        ], $this->config);
    }

    public function config(): array
    {
        return $this->config;
    }

    public function database(): Database
    {
        return $this->database;
    }

    public function inflector(): Inflector
    {
        return $this->inflector;
    }

    public function psr17Factory(): Psr17Factory
    {
        return $this->psr17Factory;
    }
}
