<?php

declare(strict_types=1);

use JsonServer\Method\Get;
use JsonServer\Server;
use JsonServer\Utils\ParsedUri;

test('should embed parent resourse in get all', function () {
    $server = new Server([
        'database-file' => __DIR__.'/../fixture/db-posts.json',
    ]);

    $parsedUrl = ParsedUri::parseUri('/posts/1/comments');
    $request = createRequest('http://localhost:8000/posts/1/comments');
    $response = createResponse(200);

    $get = new Get($server);

    $response = $get->execute($request, $response, $parsedUrl);

    $data = json_decode((string) $response->getBody(), true);

    expect($data[0]['id'])->toBe(1);
    expect($data[0]['post'])->toMatchArray([
        'id' => 1,
        'title' => 'Lorem ipsum dolor sit amet',
        'author' => 'Rodrigo',
        'content' => 'Nunc volutpat ipsum eget sapien ornare...',
    ]);

    expect($data[1]['id'])->toBe(3);
    expect($data[1]['post'])->toMatchArray([
        'id' => 1,
        'title' => 'Lorem ipsum dolor sit amet',
        'author' => 'Rodrigo',
        'content' => 'Nunc volutpat ipsum eget sapien ornare...',
    ]);
});

test('should embed children resources in get all', function () {
    $server = new Server([
        'database-file' => __DIR__.'/../fixture/db-posts.json',
    ]);

    $parsedUrl = ParsedUri::parseUri('/posts');
    $request = createRequest('http://localhost:8000/posts');
    $response = createResponse(200);

    $get = new Get($server);

    $response = $get->execute($request, $response, $parsedUrl);

    $data = json_decode((string) $response->getBody(), true);

    expect($data[0]['comments'])->toHaveCount(2);
    expect($data[0]['comments'][0])->toMatchArray([
        'id' => 1,
        'comment' => 'Pellentesque id orci sodales, dignissim massa vel',
    ]);
    expect($data[0]['comments'][1])->toMatchArray([
        'id' => 3,
        'comment' => 'Quisque velit tellus, tempus vitae condimentum nec',
    ]);
});

test('should embed parent resourse in get one', function () {
    $server = new Server([
        'database-file' => __DIR__.'/../fixture/db-posts.json',
    ]);

    $parsedUrl = ParsedUri::parseUri('/posts/1/comments/1');
    $request = createRequest('http://localhost:8000/posts/1/comments/1');
    $response = createResponse(200);

    $get = new Get($server);

    $response = $get->execute($request, $response, $parsedUrl);

    $data = json_decode((string) $response->getBody(), true);

    expect($data['id'])->toBe(1);
    expect($data['post'])->toMatchArray([
        'id' => 1,
        'title' => 'Lorem ipsum dolor sit amet',
        'author' => 'Rodrigo',
        'content' => 'Nunc volutpat ipsum eget sapien ornare...',
    ]);
});

test('should embed children resources in get one', function () {
    $server = new Server([
        'database-file' => __DIR__.'/../fixture/db-posts.json',
    ]);

    $parsedUrl = ParsedUri::parseUri('/posts/1');
    $request = createRequest('http://localhost:8000/posts/1');
    $response = createResponse(200);

    $get = new Get($server);

    $response = $get->execute($request, $response, $parsedUrl);

    $data = json_decode((string) $response->getBody(), true);

    expect($data['comments'])->toHaveCount(2);
    expect($data['comments'][0])->toMatchArray([
        'id' => 1,
        'comment' => 'Pellentesque id orci sodales, dignissim massa vel',
    ]);
    expect($data['comments'][1])->toMatchArray([
        'id' => 3,
        'comment' => 'Quisque velit tellus, tempus vitae condimentum nec',
    ]);
});
