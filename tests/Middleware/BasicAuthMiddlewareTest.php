<?php

declare(strict_types=1);

use JsonServer\Middlewares\BasicAuthMiddleware;
use JsonServer\Middlewares\Handler;
use Nyholm\Psr7\Factory\Psr17Factory;

test('should return 401 if token not present', function () {
    $middleware = new BasicAuthMiddleware([
        'user' => 'user123',
        'password' => 'pass123',
    ]);

    $request = createRequest('/posts');
    $response = $middleware->process($request, createHandler());

    expect($response->getStatusCode())->toBe(401);
    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->statusCode->toBe(401)
        ->message->toBe('Unauthorized');
});

test('should return 401 if user or password wrong', function () {
    $middleware = new BasicAuthMiddleware([
        'user' => 'user123',
        'password' => 'pass123',
    ]);

    $request = createRequest('/posts');

    $token = 'Basic '.base64_encode('userWrong:passWrong');
    $request = $request->withHeader('Authorization', $token);

    $response = $middleware->process($request, createHandler());

    expect($response->getStatusCode())->toBe(401);
    expect((string) $response->getBody())->toBeJson();
    expect((string) $response->getBody())
        ->json()
        ->statusCode->toBe(401)
        ->message->toBe('Unauthorized');
});

test('should return 20 if user or password right', function () {
    $middleware = new BasicAuthMiddleware([
        'user' => 'user123',
        'password' => 'pass123',
    ]);

    $request = createRequest('/posts');

    $token = 'Basic '.base64_encode('user123:pass123');
    $request = $request->withHeader('Authorization', $token);

    $response = $middleware->process($request, createHandler());

    expect($response->getStatusCode())->toBe(200);
});

test('should ignore a url if is in ignore param', function () {
    $middleware = new BasicAuthMiddleware([
        'user' => 'user123',
        'password' => 'pass123',
        'ignore' => [
            '/allow',
        ],
    ]);

    $request = createRequest('/allow');

    $response = $middleware->process($request, createHandler());

    expect($response->getStatusCode())->toBe(200);
});

test('should ignore a url if is in ignore param with a wildcard', function () {
    $middleware = new BasicAuthMiddleware([
        'user' => 'user123',
        'password' => 'pass123',
        'ignore' => [
            '/allow/*',
        ],
    ]);

    $request = createRequest('/allow/42');

    $response = $middleware->process($request, createHandler());

    expect($response->getStatusCode())->toBe(200);
});

test('should read credentials from file', function () {
    $middleware = new BasicAuthMiddleware(__DIR__.'/../fixture/credentials.json');

    $request = createRequest('/posts');
    $token = 'Basic '.base64_encode('user123:pass123');
    $request = $request->withHeader('Authorization', $token);

    $response = $middleware->process($request, createHandler());

    expect($response->getStatusCode())->toBe(200);
});

