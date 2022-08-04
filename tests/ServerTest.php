<?php

declare(strict_types=1);

use JsonServer\Server;
use Nyholm\Psr7\Factory\Psr17Factory;

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

test('should return data from a entity', function () {
    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts.json');

    $response = $server->handle('GET', '/posts', '');

    expect($response->getStatusCode())->toBe(200);

    $expectData = json_decode(file_get_contents(__DIR__.'/fixture/db-posts.json'), true);
    $responseData = json_decode((string) $response->getBody(), true);

    expect($responseData)->toHaveCount(2);
    expect($responseData)->toMatchArray($expectData['posts']);
});

test('should return data from a entity with a id', function () {
    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts.json');

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

test('should return error 404 if entity not found', function () {
    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts.json');

    $response = $server->handle('GET', '/entityNotFound', '');

    expect($response->getStatusCode())->toBe(404);
});

test('should return error 404 if entity does not exists', function () {
    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts.json');

    $response = $server->handle('GET', '/posts/42', '');

    expect($response->getStatusCode())->toBe(404);
});

test('should send the response to the stdout', function () {
    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts.json');

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
    $dbFileJson = __DIR__.'/fixture/db-posts-save.json';

    file_put_contents($dbFileJson, '{"posts": []}');

    $server = new Server(dbFileJson: $dbFileJson);

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

    $dataDb = json_decode(file_get_contents($dbFileJson), true);

    expect($dataDb['posts'][0])->toMatchArray([
        'id' => 1,
        'title' => 'Title Test 1',
        'author' => 'Author Test 1',
        'content' => 'Content Test 1',
    ]);

    unlink($dbFileJson);
});

test('should update data from a put request', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-update.json';

    file_put_contents($dbFileJson, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server(dbFileJson: $dbFileJson);

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

    $data = json_decode(file_get_contents($dbFileJson), true);

    expect($data['posts'][1])->toMatchArray([
        'id' => 2,
        'title' => 'Title Test changed',
        'author' => 'Author Test changed',
        'content' => 'Content Test changed',
    ]);
});

test('should create an entity if entity not exists on a request put', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-update.json';

    file_put_contents($dbFileJson, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server(dbFileJson: $dbFileJson);

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

    $data = json_decode(file_get_contents($dbFileJson), true);

    expect($data['posts'][2])->toMatchArray([
        'id' => 3,
        'title' => 'Title put new',
        'author' => 'Author put new',
        'content' => 'Content put new',
    ]);
});

test('should delete an entity', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-delete.json';

    file_put_contents($dbFileJson, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server(dbFileJson: $dbFileJson);

    $response = $server->handle('DELETE', '/posts/1', '');

    expect($response->getStatusCode())->toBe(204);

    $data = json_decode(file_get_contents($dbFileJson), true);

    expect($data['posts'])->toHaveCount(1);

    expect($data['posts'][0])->toMatchArray([
        'id' => 2,
        'title' => 'Duis quis arcu mi',
        'author' => 'Rodrigo',
        'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
    ]);
});

test('should return error if id not exists on delete', function () {
    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts.json');

    $response = $server->handle('DELETE', '/posts/42', '');

    expect($response->getStatusCode())->toBe(404);

    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->statusCode->toBe(404)
        ->message->toBe('Not Found');
});

test('should return entities with relationship', function () {
    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts.json');

    $response = $server->handle('GET', '/posts/1/comments', '');

    expect($response->getStatusCode())->toBe(200);

    $responseData = json_decode((string) $response->getBody(), true);

    expect($responseData)->toHaveCount(2);

    expect($responseData)->toMatchArray([
        [
            'id' => 1,
            'post_id' => 1,
            'comment' => 'Pellentesque id orci sodales, dignissim massa vel',
        ],
        [
            'id' => 3,
            'post_id' => 1,
            'comment' => 'Quisque velit tellus, tempus vitae condimentum nec',
        ],
    ]);
});

test('should save an entity with a relationship', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-save.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts-save.json');

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

test('should update an entity with a relationship', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-update.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts-update.json');

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

test('should return 404 if parent entitity in relationship does not exist', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-update.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts-update.json');

    $response = $server->handle('PUT', '/posts/5/comments/2', json_encode([
        'comment' => 'modified comment',
    ]));

    expect($response->getStatusCode())->toBe(404);
});

test('should change the relationship if pass the field of parent', function () {
    file_put_contents(__DIR__.'/fixture/db-posts-update.json', file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts-update.json');

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

    $server = new Server(dbFileJson: __DIR__.'/fixture/db-posts-update.json');

    $response = $server->handle('PUT', '/posts/2/comments', json_encode([
        'comment' => 'modified comment',
        'post_id' => 1,
    ]));

    expect($response->getStatusCode())->toBe(404);
});
