<?php

declare(strict_types=1);

namespace JsonServer\Middlewares;

use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BasicAuthMiddleware extends Middleware
{
    private array $ignore = [];

    private string $user;

    private string $password;

    public function __construct(array|string $credentials = 'basic-credentials.json')
    {
        if (is_string($credentials)) {
            if (! file_exists($credentials)) {
                throw new Exception('cannot open '.$credentials);
            }
            $credentials = json_decode(file_get_contents($credentials), true);
        }
        if (is_array($credentials)) {
            $this->user = $credentials['user'] ?? '';
            $this->password = $credentials['password'] ?? '';
            $this->ignore = $credentials['ignore'] ?? [];
        }
    }

    public function process(ServerRequestInterface $request, Handler $handler): ResponseInterface
    {
        if ($this->checkIgnore($request->getUri()->getPath())) {
            return $handler->handle($request);
        }

        $headerAuthorization = substr($request->getHeader('Authorization')[0] ?? '', 6);
        $basic = explode(':', base64_decode($headerAuthorization));

        if ($basic[0] === $this->user && $basic[1] === $this->password) {
            return $handler->handle($request);
        }

        $prs17Factory = new Psr17Factory();
        $response = $prs17Factory->createResponse(401);
        $response->getBody()->write(json_encode([
            'statusCode' => 401,
            'message' => 'Unauthorized',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function checkIgnore($url): bool
    {
        foreach ($this->ignore as $ignore) {
            $pattern = '/'.str_replace(['*', '/'], ['.*', "\/"], $ignore).'/';
            preg_match($pattern, $url, $match);
            if (! empty($match)) {
                return true;
            }
        }

        return false;
    }
}
