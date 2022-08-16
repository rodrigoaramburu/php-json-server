<?php

declare(strict_types=1);

use JsonServer\Middlewares\Handler;
use JsonServer\Middlewares\Middleware;
use JsonServer\Server;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

test('should send the response to the stdout', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $psr17Factory = new Psr17Factory();

    $bodyResponse = $psr17Factory->createStream('data');
    $response = $psr17Factory
                    ->createResponse(200)
                    ->withBody($bodyResponse)
                    ->withHeader('Content-type', 'application/json');

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

test('should return 400 if put request with empty body', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
    $response = $server->handle('PUT', '/posts/1', null);

    expect($response->getStatusCode())->toBe(400);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toMatchArray([
        'statusCode' => 400,
        'message' => 'Empty Body',
    ]);
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

test('should include header in request', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $middlewareCheckHeader = new class extends Middleware
    {
        public function process(ServerRequestInterface $request, Handler $handler): ResponseInterface
        {
            expect($request->getHeader('x-my-header')[0])->toBe('example-value');

            return $handler->handle($request);
        }
    };
    $server->addMiddleware($middlewareCheckHeader);
    $response = $server->handle('GET', '/posts', null, ['x-my-header' => 'example-value']);
});

test('should load config from array', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    expect($server->config())->toBeArray();
    expect($server->config())->toHaveCount(2);
    expect($server->config())->toMatchArray([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
});
