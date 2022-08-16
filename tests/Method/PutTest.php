<?php

declare(strict_types=1);

use JsonServer\Exceptions\EmptyBodyException;
use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Method\Put;
use JsonServer\Server;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;

function executePut($uri, $body): ResponseInterface
{
    $databaseFile = __DIR__.'/../fixture/db-posts-put.json';
    file_put_contents(__DIR__.'/../fixture/db-posts-put.json', file_get_contents(__DIR__.'/../fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $parsedUrl = ParsedUri::parseUri($uri);
    $request = createRequest('http://localhost:8000'.$uri, 'PUT', $body);
    $response = createResponse(200);

    $put = new Put($server);

    return $put->execute($request, $response, $parsedUrl);
}

beforeEach(function () {
    $this->databaseFile = __DIR__.'/../fixture/db-posts-put.json';
});

afterEach(function () {
    if (file_exists($this->databaseFile)) {
        unlink($this->databaseFile);
    }
});

test('should update data from a put request', function () {
    $response = executePut('/posts/2', json_encode([
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

    $data = json_decode(file_get_contents($this->databaseFile), true);

    expect($data['posts'][1])->toMatchArray([
        'id' => 2,
        'title' => 'Title Test changed',
        'author' => 'Author Test changed',
        'content' => 'Content Test changed',
    ]);
});

test('should create an resource if resource not exists on a request put', function () {
    $response = executePut('/posts/3', json_encode([
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

    $data = json_decode(file_get_contents($this->databaseFile), true);

    expect($data['posts'][2])->toMatchArray([
        'id' => 3,
        'title' => 'Title put new',
        'author' => 'Author put new',
        'content' => 'Content put new',
    ]);
});

test('should update an resource with a relationship', function () {
    $response = executePut('/posts/2/comments/2', json_encode([
        'comment' => 'modified comment',
    ]));

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode(file_get_contents($this->databaseFile), true);

    expect($data['comments'][1])->toMatchArray([
        'id' => 2,
        'post_id' => 2,
        'comment' => 'modified comment',
    ]);
});

test('should throw exception if parent resource in relationship does not exist', function () {
    $response = executePut('/posts/5/comments/2', json_encode([
        'comment' => 'modified comment',
    ]));
})->throws(NotFoundResourceException::class);

test('should change the relationship if pass the field of parent', function () {
    $response = executePut('/posts/2/comments/2', json_encode([
        'comment' => 'modified comment',
        'post_id' => 1,
    ]));

    expect($response->getStatusCode())->toBe(200);

    $data = json_decode(file_get_contents($this->databaseFile), true);

    expect($data['comments'][1])->toMatchArray([
        'id' => 2,
        'post_id' => 1,
        'comment' => 'modified comment',
    ]);
});

test('should throw exception if id not found on put request', function () {
    $response = executePut('/posts/2/comments', json_encode([
        'comment' => 'modified comment',
        'post_id' => 1,
    ]));
})->throws(NotFoundResourceException::class);

test('should throw exception if put request with empty body', function () {
    $response = executePut('/posts/1', '');
})->throws(EmptyBodyException::class);
