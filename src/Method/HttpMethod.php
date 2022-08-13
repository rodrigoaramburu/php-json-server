<?php

declare(strict_types=1);

namespace JsonServer\Method;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use JsonServer\Database;
use JsonServer\Exceptions\EmptyBodyException;
use JsonServer\Exceptions\NotFoundEntityException;
use JsonServer\Utils\ParsedUri;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class HttpMethod
{
    protected Inflector $inflector;

    public function __construct(
        protected Database $database,
        protected Psr17Factory $psr17Factory
    ) {
        $this->inflector = InflectorFactory::create()->build();
    }

    abstract public function execute(RequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface;

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
}
