<?php

declare(strict_types=1);

namespace JsonServer;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Exception;
use JsonServer\Exceptions\EmptyBodyException;
use JsonServer\Exceptions\NotFoundEntityException;
use JsonServer\Exceptions\NotFoundEntityRepositoryException;
use JsonServer\Middlewares\Middleware;
use JsonServer\Utils\ParsedUri;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Server
{
    private Inflector $inflector;

    private ?Middleware $middleware = null;

    public function __construct(string $dbFileJson = 'db.json')
    {
        $this->database = new Database(dbFileJson: $dbFileJson);
        $this->psr17Factory = new Psr17Factory();
        $this->inflector = InflectorFactory::create()->build();
    }

    public function handle(string $method, string $uri, ?string $body): ResponseInterface
    {
        $request = $this->psr17Factory
                            ->createRequest($method, $uri)
                            ->withBody($this->psr17Factory->createStream($body ?? ''));

        $response = $this->psr17Factory
                                ->createResponse(200)
                                ->withHeader('Content-type', 'application/json');

        if ($this->middleware !== null) {
            return $this->middleware->handle($request, function ($request) use ($response) {
                return $this->proccess($request, $response);
            });
        }

        return $this->proccess($request, $response);
    }

    public function proccess(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsedUri = ParsedUri::parseUri($request->getUri()->getPath());

        if (in_array($request->getMethod(), ['GET', 'POST', 'PUT', 'DELETE'])) {
            try {
                return call_user_func(
                    [$this, strtolower($request->getMethod())],
                    $parsedUri,
                    (string) $request->getBody(),
                    $response
                );
            } catch (NotFoundEntityException|NotFoundEntityRepositoryException  $e) {
                $bodyResponse = $this->psr17Factory->createStream(json_encode([
                    'statusCode' => 404,
                    'message' => 'Not Found',
                ]));

                return $response
                    ->withStatus(404)
                    ->withBody($bodyResponse);
            } catch (EmptyBodyException $e) {
                $bodyResponse = $this->psr17Factory->createStream(json_encode([
                    'statusCode' => 400,
                    'message' => 'Empty Body',
                ]));

                return $response
                    ->withStatus(400)
                    ->withBody($bodyResponse);
            }
        }
        throw new Exception('http method não encontrado');
    }

    private function get(ParsedUri $parsedUri, ?string $body, ResponseInterface $response): ResponseInterface
    {
        $query = $this->database->from($parsedUri->currentEntity->name)->query();

        if ($parsedUri->currentEntity->parent !== null) {
            $query = $query
                        ->whereParent(
                            entityName: $parsedUri->currentEntity->parent->name,
                            id: $parsedUri->currentEntity->parent->id
                        );
        }

        if ($parsedUri->currentEntity->id === null) {
            $data = $query->get();
        } else {
            $data = $query->find($parsedUri->currentEntity->id);
            if ($data === null) {
                throw new NotFoundEntityRepositoryException('entity not exists');
            }
        }

        $bodyResponse = $this->psr17Factory->createStream(json_encode($data));

        return $response->withBody($bodyResponse);
    }

    private function post(ParsedUri $parsedUri, ?string $body, ResponseInterface $response): ResponseInterface
    {
        $repository = $this->database->from($parsedUri->currentEntity->name);

        $data = $this->bodyDecode($body);

        $data = $this->includeParent($data, $parsedUri);

        $data = $repository->save(data: $data);

        $bodyResponse = $this->psr17Factory->createStream(json_encode($data));

        return $response
                ->withStatus(201)
                ->withBody($bodyResponse);
    }

    private function put(ParsedUri $parsedUri, ?string $body, ResponseInterface $response): ResponseInterface
    {
        if ($parsedUri->currentEntity->id === null) {
            throw new NotFoundEntityException('entity not found');
        }
        $repository = $this->database->from($parsedUri->currentEntity->name);

        $entityData = $this->bodyDecode($body);

        if ($parsedUri->currentEntity->parent !== null) {
            $column = $this->inflector->singularize($parsedUri->currentEntity->parent->name).'_id';
            if (! array_key_exists($column, $entityData)) {
                $entityData = $this->includeParent($entityData, $parsedUri);
            }
        }

        try {
            $data = $repository->update(
                $parsedUri->currentEntity->id,
                $entityData
            );
            $statusCode = 200;
        } catch (NotFoundEntityException $e) {
            $statusCode = 201;
            $data = $repository->save(json_decode($body, true));
        }

        $bodyResponse = $this->psr17Factory->createStream(json_encode($data));

        return $response
            ->withStatus($statusCode)
            ->withBody($bodyResponse);
    }

    public function delete(ParsedUri $parsedUri, ?string $body, ResponseInterface $response): ResponseInterface
    {
        if ($parsedUri->currentEntity->id === null) {
            throw new NotFoundEntityException('entity not found');
        }

        $repository = $this->database->from($parsedUri->currentEntity->name);

        if ($parsedUri->currentEntity->parent !== null) {
            $parentData = $this->database
                ->from($parsedUri->currentEntity->parent->name)
                   ->find($parsedUri->currentEntity->parent->id);

            $column = $this->inflector->singularize($parsedUri->currentEntity->parent->name).'_id';
            $parentIdFromEntity = $repository->find($parsedUri->currentEntity->id)[$column];

            if ($parentData === null || $parentIdFromEntity !== $parsedUri->currentEntity->parent->id) {
                throw new NotFoundEntityException('entity not found');
            }
        }

        $repository->delete($parsedUri->currentEntity->id);

        return $response->withStatus(204);
    }

    public function send(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $key => $value) {
            header("$key: {$value[0]}");
        }
        echo (string) $response->getBody();
    }

    private function includeParent(array $data, ParsedUri $parsedUri): array
    {
        if ($parsedUri->currentEntity->parent === null) {
            return $data;
        }

        $parentData = $this->database
                            ->from($parsedUri->currentEntity->parent->name)
                                ->find($parsedUri->currentEntity->parent->id);

        if ($parentData === null) {
            throw new NotFoundEntityException('entity not found');
        }

        $column = $this->inflector->singularize($parsedUri->currentEntity->parent->name).'_id';
        $data[$column] = $parsedUri->currentEntity->parent->id;

        return $data;
    }

    private function bodyDecode(?string $body): array
    {
        if ($body === null || $body === '') {
            throw new EmptyBodyException();
        }
        $result = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EmptyBodyException();
        }

        return $result;
    }

    public function addMidleware(Middleware $middleware): self
    {
        if ($this->middleware === null) {
            $this->middleware = $middleware;

            return $this;
        }

        $middleware->setNext($this->middleware);
        $this->middleware = $middleware;

        return $this;
    }
}
