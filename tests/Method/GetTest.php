<?php

declare(strict_types=1);

use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Exceptions\NotFoundResourceRepositoryException;
use JsonServer\Method\Get;
use JsonServer\Server;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;

function executeGet($uri): ResponseInterface
{
    $query = parse_url($uri);
    parse_str($query['query'] ?? '', $query);

    $server = new Server([
        'database-file' => __DIR__.'/../fixture/db-posts.json',
    ]);

    $parsedUrl = ParsedUri::parseUri($uri);
    $request = createRequest('http://localhost:8000'.$uri)->withQueryParams($query);
    $response = createResponse(200);

    $get = new Get($server);

    return $get->execute($request, $response, $parsedUrl);
}

test('should return data from a resource', function () {
    $response = executeGet('/posts');

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
    $response = executeGet('/posts/2');

    expect($response->getStatusCode())->toBe(200);

    $responseData = json_decode((string) $response->getBody(), true);

    expect($responseData)->toMatchArray([
        'id' => 2,
        'title' => 'Duis quis arcu mi',
        'author' => 'Rodrigo',
        'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
    ]);
});

test('should throw exception if collection of resources not found', function () {
    $response = executeGet('/resourceNotFound');
})->throws(NotFoundResourceRepositoryException::class);

test('should throw exception if resource does not exists', function () {
    $response = executeGet('/posts/42');
})->throws(NotFoundResourceException::class);

test('should return resources with relationship', function () {
    $response = executeGet('/posts/1/comments');

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

test('should filter resources by query params', function () {
    $response = executeGet('/posts?title=duis');

    $data = json_decode((string) $response->getBody(), true);

    expect($data)->toHaveCount(1);

    expect($data[0]['title'])->toBe('Duis quis arcu mi');
    expect($data[0]['author'])->toBe('Rodrigo');
    expect($data[0]['content'])->toBe('Suspendisse auctor dolor risus, vel posuere libero...');
});

test('should order by query params', function () {
    $server = new Server([
        'database-file' => __DIR__.'/../fixture/db-posts-shuffled.json',
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
        'database-file' => __DIR__.'/../fixture/db-posts-shuffled.json',
    ]);

    $response = $server->handle('GET', '/posts?_sort=title&_order=desc');

    $data = json_decode((string) $response->getBody(), true);
    expect($data[0]['title'])->toBe('Title 4');
    expect($data[1]['title'])->toBe('Title 3');
    expect($data[2]['title'])->toBe('Title 2');
    expect($data[3]['title'])->toBe('Title 1');
});

test('should embed parent resourse in get all', function () {
    $response = executeGet('/posts/1/comments');

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
    $response = executeGet('/posts');

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
    $response = executeGet('/posts/1/comments/1');

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
    $response = executeGet('/posts/1');

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
