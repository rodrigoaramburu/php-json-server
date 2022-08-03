<?php

declare(strict_types=1);

use JsonServer\Server;
use Nyholm\Psr7\Factory\Psr17Factory;

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
