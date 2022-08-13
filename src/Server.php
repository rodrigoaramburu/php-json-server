<?php

declare(strict_types=1);

namespace JsonServer;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use JsonServer\Exceptions\HttpException;
use JsonServer\Exceptions\MethodNotAllowedException;
use JsonServer\Method\Delete;
use JsonServer\Method\Get;
use JsonServer\Method\Post;
use JsonServer\Method\Put;
use JsonServer\Middlewares\Middleware;
use JsonServer\Utils\ParsedUri;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Server
{
    private Inflector $inflector;

    private ?Middleware $middleware = null;

    public function __construct(string $dbFileJson = 'db.json')
    {
        $this->database = new Database(dbFileJson: $dbFileJson);
        $this->psr17Factory = new Psr17Factory();
        $this->inflector = InflectorFactory::create()->build();
    }

    public function handle(string $method, string $uri, ?string $body = null, ?array $headers = []): ResponseInterface
    {
        $request = $this->psr17Factory
                            ->createRequest($method, $uri)
                            ->withBody($this->psr17Factory->createStream($body ?? ''));

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        $response = $this->psr17Factory
                                ->createResponse(200)
                                ->withHeader('Content-type', 'application/json');

        if ($this->middleware !== null) {
            return $this->middleware->handle($request, function ($request) use ($response) {
                return $this->process($request, $response);
            });
        }

        return $this->process($request, $response);
    }

    public function process(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsedUri = ParsedUri::parseUri($request->getUri()->getPath());

        try {
            $httpMethod = match ($request->getMethod()) {
                'GET' => new Get($this->database, $this->psr17Factory),
                'POST' => new Post($this->database, $this->psr17Factory),
                'PUT' => new Put($this->database, $this->psr17Factory),
                'DELETE' => new Delete($this->database, $this->psr17Factory),
                default => null
            };

            if ($httpMethod == null) {
                throw new MethodNotAllowedException();
            }

            return $httpMethod->execute($request, $response, $parsedUri);
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
        if ($this->middleware === null) {
            $this->middleware = $middleware;

            return $this;
        }

        $middleware->setNext($this->middleware);
        $this->middleware = $middleware;

        return $this;
    }
}
