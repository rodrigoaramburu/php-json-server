<?php

declare(strict_types=1);

use JsonServer\Middlewares\Handler;
use JsonServer\Middlewares\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

test('should add next middlware in the end of the chain', function () {
    $middleware1 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            return $handler->handle($request);
        }
    };
    $middleware2 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            return $handler->handle($request);
        }
    };
    $middleware3 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            return $handler->handle($request);
        }
    };

    $middleware1->setNext($middleware2);
    $middleware2->setNext($middleware3);

    expect($middleware1->next())->toBe($middleware2);
    expect($middleware1->next()->next())->toBe($middleware3);
});

test('should call process in order', function () {
    $middleware1 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            $header = 'Middleware 1 -';
            $request = $request->withHeader('requestHeaderExpected', $header);

            $response = $handler->handle($request);

            $responseHeader = $response->getHeader('responseHeaderExpected')[0].' RMiddleware 1';
            $response = $response->withHeader('responseHeaderExpected', $responseHeader);

            return $response;
        }
    };
    $middleware2 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            $header = $request->getHeader('requestHeaderExpected')[0].' Middleware 2 -';
            $request = $request->withHeader('requestHeaderExpected', $header);

            $response = $handler->handle($request);

            $responseHeader = $response->getHeader('responseHeaderExpected')[0].' RMiddleware 2 -';
            $response = $response->withHeader('responseHeaderExpected', $responseHeader);

            return $response;
        }
    };
    $middleware3 = new class extends Middleware
    {
        public function process(RequestInterface $request, Handler $handler): ResponseInterface
        {
            $header = $request->getHeader('requestHeaderExpected')[0].' Middleware 3';
            $request = $request->withHeader('requestHeaderExpected', $header);

            $response = $handler->handle($request);

            $response = $response->withHeader('responseHeaderExpected', 'RMiddleware 3 -');

            return $response;
        }
    };

    $middleware1->setNext($middleware2);
    $middleware2->setNext($middleware3);

    $request = createRequest('POST', '/posts/1');

    $response = $middleware1->handle($request, function ($request) {
        $header = $request->getHeader('requestHeaderExpected')[0];
        expect($header)->toBe('Middleware 1 - Middleware 2 - Middleware 3');

        return createResponse(200);
    });

    $header = $response->getHeader('responseHeaderExpected')[0];
    expect($header)->toBe('RMiddleware 3 - RMiddleware 2 - RMiddleware 1');
});
