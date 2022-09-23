<?php

declare(strict_types=1);

use JsonServer\Database;
use JsonServer\Middlewares\Handler;
use JsonServer\Middlewares\Middleware;
use JsonServer\Server;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

afterEach(function () {
    $files = [
        __DIR__.'/fixture/db-posts-save.json',
        __DIR__.'/fixture/db-posts-update.json',
        __DIR__.'/fixture/db-posts-delete.json',
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('should return data from a resource', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = $server->handle('GET', '/posts', '');

    expect($response->getStatusCode())->toBe(200);

    $responseData = json_decode((string) $response->getBody(), true);

    expect($responseData)->toHaveCount(2);

    expect($responseData[0]['id'])->toBe(1);
    expect($responseData[0]['title'])->toBe('Lorem ipsum dolor sit amet');
    expect($responseData[0]['author'])->toBe('Rodrigo');
    expect($responseData[0]['content'])->toBe('Nunc volutpat ipsum eget sapien ornare...');

    expect($responseData[1]['id'])->toBe(2);
    expect($responseData[1]['title'])->toBe('Duis quis arcu mi');
    expect($responseData[1]['author'])->toBe('Rodrigo');
    expect($responseData[1]['content'])->toBe('Suspendisse auctor dolor risus, vel posuere libero...');
});

test('should return data from a resource with a id', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = $server->handle('GET', '/posts/2', '');

    expect($response->getStatusCode())->toBe(200);

    $responseData = json_decode((string) $response->getBody(), true);

    expect($responseData)->toMatchArray([
        'id' => 2,
        'title' => 'Duis quis arcu mi',
        'author' => 'Rodrigo',
        'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
    ]);
});

test('should return error 404 if resource not found', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = $server->handle('GET', '/resourceNotFound', '');

    expect($response->getStatusCode())->toBe(404);
});

test('should return error 404 if resource does not exists', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = $server->handle('GET', '/posts/42', '');

    expect($response->getStatusCode())->toBe(404);
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

test('should return 400 if post request with empty body', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
    $response = $server->handle('POST', '/posts', null);

    expect($response->getStatusCode())->toBe(400);

    $data = json_decode((string) $response->getBody(), true);
    expect($data)->toMatchArray([
        'statusCode' => 400,
        'message' => 'Empty Body',
    ]);
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

test('should return 400 if post request with body format wrong', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
    $response = $server->handle('POST', '/posts', 'DDSS{}');

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

    $middleware1 = new class () extends Middleware {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            return $handler->handle($request)->withHeader('Md1', 'teste 1');
        }
    };

    $middleware2 = new class () extends Middleware {
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

    $middlewareCheckHeader = new class () extends Middleware {
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
    $server->addMiddleware($middlewareCheckHeader);
    $response = $server->handle('GET', '/posts', null, ['x-my-header' => 'example-value']);
});

test('should filter resources by query params', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
    $response = $server->handle('GET', '/posts?title=duis');

    $data = json_decode((string) $response->getBody(), true);

    expect($data)->toHaveCount(1);

    expect($data[0]['title'])->toBe('Duis quis arcu mi');
    expect($data[0]['author'])->toBe('Rodrigo');
    expect($data[0]['content'])->toBe('Suspendisse auctor dolor risus, vel posuere libero...');
});

test('should order by query params', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts-shuffled.json',
    ]);
    $response = $server->handle('GET', '/posts?_sort=title');

    $data = json_decode((string) $response->getBody(), true);
    expect($data[0]['title'])->toBe('Title 1');
    expect($data[1]['title'])->toBe('Title 2');
    expect($data[2]['title'])->toBe('Title 3');
    expect($data[3]['title'])->toBe('Title 4');
});

test('should order by query params in desc order', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts-shuffled.json',
    ]);

    $response = $server->handle('GET', '/posts?_sort=title&_order=desc');

    $data = json_decode((string) $response->getBody(), true);
    expect($data[0]['title'])->toBe('Title 4');
    expect($data[1]['title'])->toBe('Title 3');
    expect($data[2]['title'])->toBe('Title 2');
    expect($data[3]['title'])->toBe('Title 1');
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

test('should add cors header', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = $server->handle('GET', '/posts');

    expect($response->getHeader('Access-Control-Allow-Origin')[0])->toBe('*');
    expect($response->getHeader('Access-Control-Allow-Headers')[0])->toBe('*');
    expect($response->getHeader('Access-Control-Allow-Methods')[0])->toBe('GET, POST, PUT, DELETE, OPTIONS');
});
