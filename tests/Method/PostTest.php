<?php

declare(strict_types=1);

use JsonServer\Exceptions\EmptyBodyException;
use JsonServer\Method\Post;
use JsonServer\Server;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;

function executePost($uri, $body): ResponseInterface
{
    $databaseFile = __DIR__.'/../fixture/db-posts-save.json';
    file_put_contents(__DIR__.'/../fixture/db-posts-save.json', file_get_contents(__DIR__.'/../fixture/db-posts.json'));

    $server = new Server([
        'database-file' => $databaseFile,
    ]);

    $parsedUrl = ParsedUri::parseUri($uri);
    $request = createRequest('http://localhost:8000'.$uri, 'POST', $body);
    $response = createResponse(200);

    $post = new Post($server);

    return $post->execute($request, $response, $parsedUrl);
}

beforeEach(function () {
    $this->databaseFile = __DIR__.'/../fixture/db-posts-save.json';
});

afterEach(function () {
    if (file_exists($this->databaseFile)) {
        unlink($this->databaseFile);
    }
});

test('should save data from a post request', function () {
    $response = executePost('/posts', json_encode([
        'title' => 'Title Test 1',
        'author' => 'Author Test 1',
        'content' => 'Content Test 1',
    ]));

    expect($response->getStatusCode())->toBe(201);

    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->id->toBe(3)
        ->title->toBe('Title Test 1')
        ->author->toBe('Author Test 1')
        ->content->toBe('Content Test 1');

    $dataDb = json_decode(file_get_contents($this->databaseFile), true);

    expect($dataDb['posts'][2])->toMatchArray([
        'id' => 3,
        'title' => 'Title Test 1',
        'author' => 'Author Test 1',
        'content' => 'Content Test 1',
    ]);
});

test('should save an resource with a relationship', function () {
    file_put_contents(__DIR__.'/../fixture/db-posts-save.json', file_get_contents(__DIR__.'/../fixture/db-posts.json'));

    $response = executePost('/posts/2/comments', json_encode([
        'comment' => 'comment in a relationship',
    ]));

    expect($response->getStatusCode())->toBe(201);

    $responseBody = json_decode((string) $response->getBody(), true);
    expect($responseBody)->toMatchArray([
        'id' => 4,
        'comment' => 'comment in a relationship',
        'post' => [
            'id' => 2,
            'title' => 'Duis quis arcu mi',
            'author' => 'Rodrigo',
            'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
        ],
    ]);

    $data = json_decode(file_get_contents(__DIR__.'/../fixture/db-posts-save.json'), true);

    expect($data['comments'][3])->toMatchArray([
        'id' => 4,
        'post_id' => 2,
        'comment' => 'comment in a relationship',
    ]);
});

test('should throw exception if post request with empty body', function () {
    $response = executePost('/posts', '');
})->throws(EmptyBodyException::class);

test('should throw exception if post request with body format wrong', function () {
    $response = executePost('/posts', 'DDSS{}');
})->throws(EmptyBodyException::class);
