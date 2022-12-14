<?php

declare(strict_types=1);

namespace JsonServer\Method;

use Doctrine\Inflector\Inflector;
use JsonServer\Database;
use JsonServer\Exceptions\EmptyBodyException;
use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Server;
use JsonServer\Utils\ParsedUri;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpMethod
{
    public function __construct(private Server $server)
    {
    }

    abstract public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface;

    protected function bodyDecode(?string $body): array
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

    protected function includeParent(array $data, ParsedUri $parsedUri): array
    {
        if ($parsedUri->currentResource->parent === null) {
            return $data;
        }

        $parentData = $this->server->database()
                                        ->from($parsedUri->currentResource->parent->name)
                                            ->query()
                                            ->find($parsedUri->currentResource->parent->id);

        if ($parentData === null) {
            throw new NotFoundResourceException();
        }

        $column = $this->server->inflector()->singularize($parsedUri->currentResource->parent->name).'_id';
        $data[$column] = $parsedUri->currentResource->parent->id;

        return $data;
    }

    public function database(): Database
    {
        return $this->server->database();
    }

    public function psr17Factory(): Psr17Factory
    {
        return $this->server->psr17Factory();
    }

    public function inflector(): Inflector
    {
        return $this->server->inflector();
    }

    public function config(string $name): mixed
    {
        return $this->server->config()[$name];
    }

    protected function embedParent($resources): array
    {
        for ($i = 0; $i < count($resources); $i++) {
            $keys = array_filter(array_keys($resources[$i]), fn ($key) => str_ends_with($key, '_id'));

            foreach ($keys as $key) {
                $field = str_replace('_id', '', $key);
                $resourceName = $this->inflector()->pluralize($field);
                $resourceParent = $this->database()->from($resourceName)->query()->find($resources[$i][$key]);
                $resources[$i][$field] = $resourceParent;
                unset($resources[$i][$key]);
            }
        }

        return $resources;
    }

    protected function embedChildren($resources, $resourceName): array
    {
        $resourceNameFieldId = $this->inflector()->singularize($resourceName).'_id';
        $childrenResourceNames = $this->database()->embedResources()[$resourceName] ?? [];
        for ($i = 0; $i < count($resources); $i++) {
            foreach ($childrenResourceNames as $childResourceName) {
                $resources[$i][$childResourceName] = $this->database()
                    ->from($childResourceName)
                    ->query()
                    ->where($resourceNameFieldId, (string) $resources[$i]['id'])
                    ->get();
                $resources[$i][$childResourceName] = array_map(function ($resource) use ($resourceNameFieldId) {
                    unset($resource[$resourceNameFieldId]);

                    return $resource;
                }, $resources[$i][$childResourceName]);
            }
        }

        return $resources;
    }
}
