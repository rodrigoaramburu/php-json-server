<?php

declare(strict_types=1);

use JsonServer\Database;
use JsonServer\Exceptions\MethodNotAllowedException;
use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Method\Delete;
use JsonServer\Method\Get;
use JsonServer\Method\HttpMethod;
use JsonServer\Method\Post;
use JsonServer\Method\Put;
use JsonServer\Middlewares\Handler;
use JsonServer\Middlewares\Middleware;
use JsonServer\Server;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

test('should call process with object request and response with request data', function () {
    $server = new class extends Server
    {
        public function __construct()
        {
            parent::__construct(['database-file' => __DIR__.'/fixture/db-posts.json']);
        }

        public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
        {
            expect($request->getMethod())->toBe('POST');
            expect($request->getUri()->getPath())->toBe('/posts');
            expect((string) $request->getBody())->toBe('body text');
            expect($request->getHeaders())->toMatchArray([
                'header1' => ['valueHeader1'],
            ]);
            expect($request->getQueryParams())->toMatchArray([
                'param1' => 'teste1',
                'param2' => 'teste2',
            ]);

            return $response;
        }
    };

    $server->handle('POST', '/posts?param1=teste1&param2=teste2', 'body text', ['header1' => 'valueHeader1']);
});

test('should return httpMethod object according to request method', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $httpMethodGet = $server->httpMethodHandler('GET');
    expect($httpMethodGet)->toBeInstanceOf(Get::class);

    $httpMethodGet = $server->httpMethodHandler('POST');
    expect($httpMethodGet)->toBeInstanceOf(Post::class);

    $httpMethodGet = $server->httpMethodHandler('PUT');
    expect($httpMethodGet)->toBeInstanceOf(Put::class);

    $httpMethodGet = $server->httpMethodHandler('DELETE');
    expect($httpMethodGet)->toBeInstanceOf(Delete::class);
});

test('should throw exception if httpMethod not found', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $httpMethodHandler = $server->httpMethodHandler('TEST');
})->throws(MethodNotAllowedException::class);

test('should call method execute of httpMehtodHandler', function () {
    $server = new class extends Server
    {
        public function __construct()
        {
            parent::__construct(['database-file' => __DIR__.'/fixture/db-posts.json']);
        }

        public function httpMethodHandler(string $httpMethod): HttpMethod
        {
            return new class($this) extends HttpMethod
            {
                public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
                {
                    expect($request)->toBeInstanceOf(ServerRequestInterface::class);
                    expect($response)->toBeInstanceOf(ResponseInterface::class);

                    return $response;
                }
            };
        }
    };

    $server->handle('GET', '/posts');
});

test('should config erro in response if httpMehtodHandler throw http exception', function () {
    $server = new class extends Server
    {
        public function __construct()
        {
            parent::__construct(['database-file' => __DIR__.'/fixture/db-posts.json']);
        }

        public function httpMethodHandler(string $httpMethod): HttpMethod
        {
            return new class($this) extends HttpMethod
            {
                public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
                {
                    throw new NotFoundResourceException();

                    return $response;
                }
            };
        }
    };

    $response = $server->handle('GET', '/posts/1');

    expect($response->getStatusCode())->toBe(404);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toMatchArray([
        'statusCode' => 404,
        'message' => 'Not Found',
    ]);
});

test('should send the response to the stdout', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = createResponse(200, 'data', ['Content-type' => 'application/json']);

    ob_start();
    $server->send($response);
    $result = ob_get_contents();
    ob_end_clean();

    $headers = xdebug_get_headers();

    expect($result)->toBe('data');
    expect($headers)->toContain('Content-type: application/json');
});

test('should accept null body', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
    $response = $server->handle('GET', '/posts', null);

    expect($response->getStatusCode())->toBe(200);
});

test('should call midlleware', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $middleware1 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            return $handler->handle($request)->withHeader('Md1', 'teste 1');
        }
    };

    $middleware2 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            return $handler->handle($request)->withHeader('Md2', 'teste 2');
        }
    };

    $server
        ->addMiddleware($middleware1)
        ->addMiddleware($middleware2);

    $response = $server->handle('GET', '/posts', null);

    expect($response->getHeader('Md1')[0])->toBe('teste 1');
    expect($response->getHeader('Md2')[0])->toBe('teste 2');
});

test('should inject the database and config into middleware', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $middleware = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            expect($this->database())->toBeInstanceOf(Database::class);
            expect($this->config())->toBeArray();
            expect($this->config())->toMatchArray([
                'database-file' => __DIR__.'/fixture/db-posts.json',
            ]);

            return $handler->handle($request);
        }
    };

    $server->addMiddleware($middleware);

    $response = $server->handle('GET', '/posts');
});

test('should load config from array', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    expect($server->config())->toBeArray();
    expect($server->config())->toHaveCount(1);
    expect($server->config())->toMatchArray([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
});
