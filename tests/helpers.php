<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function createRequest(string $url, string $body = '', array $headers = []): ServerRequestInterface
{
    $psr17Factory = new Psr17Factory();

    $request = $psr17Factory->createServerRequest('GET', $psr17Factory->createUri($url));

    foreach ($headers as $key => $value) {
        $request = $request->withHeader($key, $value);
    }

    $bodyStream = $psr17Factory->createStream($body);

    return $request->withBody($bodyStream);
}

function createResponse(int $code, string $body = ''): ResponseInterface
{
    $psr17Factory = new Psr17Factory();

    $response = $psr17Factory->createResponse($code);

    $bodyStream = $psr17Factory->createStream($body);

    return  $response->withBody($bodyStream);
}
