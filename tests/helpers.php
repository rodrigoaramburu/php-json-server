<?php

declare(strict_types=1);

use JsonServer\Middlewares\Handler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Minicli\Output\Theme\DefaultTheme;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function createRequest(string $url, string $method = 'GET', string $body = '', array $headers = []): ServerRequestInterface
{
    $psr17Factory = new Psr17Factory();

    $request = $psr17Factory->createServerRequest($method, $psr17Factory->createUri($url));

    foreach ($headers as $key => $value) {
        $request = $request->withHeader($key, $value);
    }

    $bodyStream = $psr17Factory->createStream($body);

    return $request->withBody($bodyStream);
}

function createResponse(int $code, string $body = '', array $headers = []): ResponseInterface
{
    $psr17Factory = new Psr17Factory();

    $response = $psr17Factory->createResponse($code);

    $bodyStream = $psr17Factory->createStream($body);
    foreach ($headers as $key => $value) {
        $response = $response->withHeader($key, $value);
    }

    return  $response->withBody($bodyStream);
}

function createHandler(): Handler
{
    $handler = new Handler(function () {
        $psr17Factory = new Psr17Factory();

        return $psr17Factory->createResponse();
    });

    return $handler;
}




function s(string $style)
{
    $theme = new DefaultTheme();
    return "\e[0m\e[" . implode(';', $theme->getStyle($style)) .'m';
}
