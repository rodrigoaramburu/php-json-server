<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Database;
use JsonServer\Utils\ParsedUri;
use Doctrine\Inflector\Inflector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Doctrine\Inflector\InflectorFactory;
use Psr\Http\Message\ServerRequestInterface;
use JsonServer\Exceptions\EmptyBodyException;
use JsonServer\Exceptions\NotFoundResourceException;

abstract class HttpMethod
{
    protected Inflector $inflector;

    public function __construct(
        protected Database $database,
        protected Psr17Factory $psr17Factory
    ) {
        $this->inflector = InflectorFactory::create()->build();
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

        $parentData = $this->database
                            ->from($parsedUri->currentResource->parent->name)
                                ->find($parsedUri->currentResource->parent->id);

        if ($parentData === null) {
            throw new NotFoundResourceException();
        }

        $column = $this->inflector->singularize($parsedUri->currentResource->parent->name).'_id';
        $data[$column] = $parsedUri->currentResource->parent->id;

        return $data;
    }
}
