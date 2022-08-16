<?php

declare(strict_types=1);

use JsonServer\Middlewares\Handler;
use JsonServer\Middlewares\Middleware;
use JsonServer\Server;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

test('should save data from a post request', function () {
    $databaseFile = __DIR__.'/fixture/db-posts-save.json';

    file_put_contents($databaseFile, '{"posts": []}');

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $response = $server->handle('POST', '/posts', json_encode([
        'title' => 'Title Test 1',
        'author' => 'Author Test 1',
        'content' => 'Content Test 1',
    ]));

    expect($response->getStatusCode())->toBe(201);

    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->id->toBe(1)
        ->title->toBe('Title Test 1')
        ->author->toBe('Author Test 1')
        ->content->toBe('Content Test 1');

    $dataDb = json_decode(file_get_contents($databaseFile), true);

    expect($dataDb['posts'][0])->toMatchArray([
        'id' => 1,
        'title' => 'Title Test 1',
        'author' => 'Author Test 1',
        'content' => 'Content Test 1',
    ]);

    unlink($databaseFile);
});

test('should update data from a put request', function () {
    $databaseFile = __DIR__.'/fixture/db-posts-update.json';

    file_put_contents($databaseFile, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $response = $server->handle('PUT', '/posts/2', json_encode([
        'id' => 2,
        'title' => 'Title Test changed',
        'author' => 'Author Test changed',
        'content' => 'Content Test changed',
    ]));

    expect($response->getStatusCode())->toBe(200);

    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->id->toBe(2)
        ->title->toBe('Title Test changed')
        ->author->toBe('Author Test changed')
        ->content->toBe('Content Test changed');

    $data = json_decode(file_get_contents($databaseFile), true);

    expect($data['posts'][1])->toMatchArray([
        'id' => 2,
        'title' => 'Title Test changed',
        'author' => 'Author Test changed',
        'content' => 'Content Test changed',
    ]);
});

test('should create an resource if resource not exists on a request put', function () {
    $databaseFile = __DIR__.'/fixture/db-posts-update.json';

    file_put_contents($databaseFile, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $response = $server->handle('PUT', '/posts/3', json_encode([
        'id' => 3,
        'title' => 'Title put new',
        'author' => 'Author put new',
        'content' => 'Content put new',
    ]));

    expect($response->getStatusCode())->toBe(201);

    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->id->toBe(3)
        ->title->toBe('Title put new')
        ->author->toBe('Author put new')
        ->content->toBe('Content put new');

    $data = json_decode(file_get_contents($databaseFile), true);

    expect($data['posts'][2])->toMatchArray([
        'id' => 3,
        'title' => 'Title put new',
        'author' => 'Author put new',
        'content' => 'Content put new',
    ]);
});

test('should delete an resource', function () {
    $databaseFile = __DIR__.'/fixture/db-posts-delete.json';

    file_put_contents($databaseFile, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $response = $server->handle('DELETE', '/posts/1', '');

    expect($response->getStatusCode())->toBe(204);

    $data = json_decode(file_get_contents($databaseFile), true);

    expect($data['posts'])->toHaveCount(1);

    expect($data['posts'][0])->toMatchArray([
        'id' => 2,
        'title' => 'Duis quis arcu mi',
        'author' => 'Rodrigo',
        'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
    ]);
});

test('should return error if id not exists on delete', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = $server->handle('DELETE', '/posts/42', '');

    expect($response->getStatusCode())->toBe(404);

    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->statusCode->toBe(404)
        ->message->toBe('Not Found');
});

test('should return resources with relationship', function () {
    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);

    $response = $server->handle('GET', '/posts/1/comments', '');

    expect($response->getStatusCode())->toBe(200);

    $responseData = json_decode((string) $response->getBody(), true);

    expect($responseData)->toHaveCount(2);

    expect($responseData)->toMatchArray([
        [
            'id' => 1,
            'comment' => 'Pellentesque id orci sodales, dignissim massa vel',
            'post' => [
                'id' => 1,
                'title' => 'Lorem ipsum dolor sit amet',
                'author' => 'Rodrigo',
                'content' => 'Nunc volutpat ipsum eget sapien ornare...',
            ],
        ],
        [
            'id' => 3,
            'comment' => 'Quisque velit tellus, tempus vitae condimentum nec',
            'post' => [
                'id' => 1,
                'title' => 'Lorem ipsum dolor sit amet',
                'author' => 'Rodrigo',
                'content' => 'Nunc volutpat ipsum eget sapien ornare...',
            ],
        ],
    ]);
});

test('should save an resource with a relationship', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-save.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts-save.json',
    ]);

    $response = $server->handle('POST', '/posts/2/comments', json_encode([
        'comment' => 'comment in a relationship',
    ]));

    expect($response->getStatusCode())->toBe(201);

    $data = json_decode(file_get_contents(__DIR__.'/fixture/db-posts-save.json'), true);

    expect($data['comments'][3])->toMatchArray([
        'id' => 4,
        'post_id' => 2,
        'comment' => 'comment in a relationship',
    ]);
});

test('should update an resource with a relationship', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-update.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts-update.json',
    ]);

    $response = $server->handle('PUT', '/posts/2/comments/2', json_encode([
        'comment' => 'modified comment',
    ]));

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode(file_get_contents(__DIR__.'/fixture/db-posts-update.json'), true);

    expect($data['comments'][1])->toMatchArray([
        'id' => 2,
        'post_id' => 2,
        'comment' => 'modified comment',
    ]);
});

test('should return 404 if parent resource in relationship does not exist', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-update.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts-update.json',
    ]);

    $response = $server->handle('PUT', '/posts/5/comments/2', json_encode([
        'comment' => 'modified comment',
    ]));

    expect($response->getStatusCode())->toBe(404);
});

test('should change the relationship if pass the field of parent', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-update.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts-update.json',
    ]);

    $response = $server->handle('PUT', '/posts/2/comments/2', json_encode([
        'comment' => 'modified comment',
        'post_id' => 1,
    ]));

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode(file_get_contents(__DIR__.'/fixture/db-posts-update.json'), true);

    expect($data['comments'][1])->toMatchArray([
        'id' => 2,
        'post_id' => 1,
        'comment' => 'modified comment',
    ]);
});

test('should return 404 if id not found on put request', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-update.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => __DIR__.'/fixture/db-posts-update.json',
    ]);

    $response = $server->handle('PUT', '/posts/2/comments', json_encode([
        'comment' => 'modified comment',
        'post_id' => 1,
    ]));

    expect($response->getStatusCode())->toBe(404);
});

test('should return 404 if parent resource id not exist', function () {
    $databaseFile = __DIR__.'/fixture/db-posts-delete.json';

    file_put_contents($databaseFile, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $response = $server->handle('DELETE', '/posts/5/comments/2', '');

    expect($response->getStatusCode())->toBe(404);

    $data = json_decode(file_get_contents($databaseFile), true);

    expect($data['comments'][1])->toMatchArray([
        'id' => 2,
        'post_id' => 2,
        'comment' => 'Maecenas elit dui, venenatis ut erat vitae',
    ]);
});

test('should return 404 if resource id not belongs to parent', function () {
    $databaseFile = __DIR__.'/fixture/db-posts-delete.json';

    file_put_contents($databaseFile, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $response = $server->handle('DELETE', '/posts/1/comments/2', '');

    expect($response->getStatusCode())->toBe(404);

    $data = json_decode(file_get_contents($databaseFile), true);

    expect($data['comments'][1])->toMatchArray([
        'id' => 2,
        'post_id' => 2,
        'comment' => 'Maecenas elit dui, venenatis ut erat vitae',
    ]);
});

test('should return 404 if not send a id on delete request', function () {
    $databaseFile = __DIR__.'/fixture/db-posts-delete.json';

    file_put_contents($databaseFile, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $response = $server->handle('DELETE', '/posts/1/comments', '');

    expect($response->getStatusCode())->toBe(404);
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
    expect($server->config())->toHaveCount(2);
    expect($server->config())->toMatchArray([
        'database-file' => __DIR__.'/fixture/db-posts.json',
    ]);
});
