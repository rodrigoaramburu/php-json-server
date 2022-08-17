<?php

declare(strict_types=1);

use JsonServer\Middlewares\StaticMiddleware;

test('should return a response from an array', function () {
    $middleware = new StaticMiddleware(routes: [
        '/tests/static' => [
            'POST' => [
                'body' => 'a body response',
                'statusCode' => 201,
                'headers' => [
                    'my-header' => '1234',
                    'header-two' => '4321',
                ],
            ],
        ],
    ]);

    $request = createRequest('http://localhost:8000/tests/static', 'POST');

    $response = $middleware->handle($request, function () {
    });

    expect($response->getStatusCode())->toBe(201);
    expect((string) $response->getBody())->toBe('a body response');
    expect($response->getHeader('my-header')[0])->toBe('1234');
    expect($response->getHeader('header-two')[0])->toBe('4321');
});

test('should return a response from an file', function () {
    $middleware = new StaticMiddleware(routes: __DIR__.'/../fixture/static.json');

    $request = createRequest('http://localhost:8000/tests/static', 'POST');

    $response = $middleware->handle($request, function () {
        return createResponse(200);
    });

    expect($response->getStatusCode())->toBe(201);
    expect((string) $response->getBody())->toBe('a body response');
    expect($response->getHeader('my-header')[0])->toBe('1234');
    expect($response->getHeader('header-two')[0])->toBe('4321');
});

test('shuold throw exception routes file not valid', function () {
    $middleware = new StaticMiddleware(routes: __DIR__.'/../fixture/static-with-error.json');
})->throws(Exception::class, 'cannot read file '.__DIR__.'/../fixture/static-with-error.json');

test('shuold throw exception routes file not exist', function () {
    $middleware = new StaticMiddleware(routes: __DIR__.'/../fixture/static-missing.json');
})->throws(Exception::class, 'cannot open file '.__DIR__.'/../fixture/static-missing.json');

test('should get response body from file if specified', function () {
    $middleware = new StaticMiddleware(routes: __DIR__.'/../fixture/static-with-body-file.json');

    $request = createRequest('http://localhost:8000/static-body-file', 'POST');

    $response = $middleware->handle($request, function () {
        return createResponse(200);
    });

    expect($response->getStatusCode())->toBe(201);
    expect((string) $response->getBody())->toBe('a body response from file');
});

test('should throw exception response body from file if specified but not exists', function () {
    $middleware = new StaticMiddleware(routes: __DIR__.'/../fixture/static-with-body-file-missing.json');

    $request = createRequest('http://localhost:8000/static-body-file-missing', 'POST');

    try {
        $response = $middleware->handle($request, function () {
            return createResponse(200);
        });
        $this->assertTrue(false, 'exception expected');
    } catch (Exception $e) {
        expect($e->getMessage())->toStartWith('file not found');
    }
});

test('should pass forward if route not found', function(){
    $middleware = new StaticMiddleware(routes: __DIR__.'/../fixture/static.json');

    $request = createRequest('http://localhost:8000/tests/static-missing', 'POST');

    $callHandler = false;
    $response = $middleware->handle($request, function () use (&$callHandler){
        $callHandler = true;
        return createResponse(200);
    });

    expect($callHandler)->toBeTrue();
});
