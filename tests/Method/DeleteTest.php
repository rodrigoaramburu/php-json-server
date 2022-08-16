<?php

declare(strict_types=1);

use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Method\Delete;
use JsonServer\Server;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;

function executeDelete($uri): ResponseInterface
{
    $databaseFile = __DIR__.'/../fixture/db-posts-delete.json';
    file_put_contents(__DIR__.'/../fixture/db-posts-delete.json', file_get_contents(__DIR__.'/../fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $parsedUrl = ParsedUri::parseUri($uri);
    $request = createRequest('http://localhost:8000'.$uri, 'DELETE');
    $response = createResponse(200);

    $delete = new Delete($server);

    return $delete->execute($request, $response, $parsedUrl);
}

beforeEach(function () {
    $this->databaseFile = __DIR__.'/../fixture/db-posts-delete.json';
});

afterEach(function () {
    if (file_exists($this->databaseFile)) {
        unlink($this->databaseFile);
    }
});

test('should delete a resource', function () {
    $response = executeDelete('/posts/1');

    expect($response->getStatusCode())->toBe(204);

    $data = json_decode(file_get_contents($this->databaseFile), true);

    expect($data['posts'])->toHaveCount(1);

    expect($data['posts'][0])->toMatchArray([
        'id' => 2,
        'title' => 'Duis quis arcu mi',
        'author' => 'Rodrigo',
        'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
    ]);
});

test('should throw exception if id not exists on delete', function () {
    $response = executeDelete('/posts/42');
})->throws(NotFoundResourceException::class);

test('should throw exception if parent resource id not exist', function () {
    $response = executeDelete('/posts/42/comments/2');
})->throws(NotFoundResourceException::class);

test('should throw exception if resource id not belongs to parent', function () {
    $response = executeDelete('/posts/1/comments/2');
})->throws(NotFoundResourceException::class);

test('should throw exception if not send a id on delete request', function () {
    $response = executeDelete('/posts/1/comments');
})->throws(NotFoundResourceException::class);
