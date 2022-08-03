<?php

declare(strict_types=1);

namespace JsonServer;

use Exception;
use JsonServer\Exceptions\NotFoundEntityException;
use JsonServer\Utils\ParsedUri;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class Server
{
    public function __construct(string $dbFileJson = 'db.json')
    {
        $this->database = new Database(dbFileJson: $dbFileJson);
        $this->psr17Factory = new Psr17Factory();
    }

    public function handle(string $method, string $uri, string $body): ResponseInterface
    {
        $parsedUri = ParsedUri::parseUri($uri);

        if (in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
            try {
                return call_user_func([$this, strtolower($method)], $parsedUri, $body);
            } catch (NotFoundEntityException $e) {
                $bodyResponse = $this->psr17Factory->createStream(json_encode([
                    'statusCode' => 404,
                    'message' => 'Not Found',
                ]));

                return $this->psr17Factory->createResponse(404)->withBody($bodyResponse)->withHeader('Content-type', 'application/json');
            }
        }
        throw new Exception('http method não encontrado');
    }

    private function get(ParsedUri $parsedUri, string $body): ResponseInterface
    {
        $repository = $this->database->from($parsedUri->entity(0)->name);

        if ($parsedUri->entity(0)->id === null) {
            $data = $repository->get();
        } else {
            $data = $repository->find($parsedUri->entity(0)->id);
            if ($data === null) {
                throw new NotFoundEntityException('entity not exists');
            }
        }

        $bodyResponse = $this->psr17Factory->createStream(json_encode($data));

        return $this->psr17Factory->createResponse(200)->withBody($bodyResponse)->withHeader('Content-type', 'application/json');
    }

    private function post(ParsedUri $parsedUri, string $body): ResponseInterface
    {
        $repository = $this->database->from($parsedUri->entity(0)->name);

        $data = $repository->save(json_decode($body, true));

        $bodyResponse = $this->psr17Factory->createStream(json_encode($data));

        return $this->psr17Factory
            ->createResponse(201)
            ->withBody($bodyResponse)
            ->withHeader('Content-type', 'application/json');
    }

    public function send(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $key => $value) {
            header("$key: {$value[0]}");
        }
        echo (string) $response->getBody();
    }
}
