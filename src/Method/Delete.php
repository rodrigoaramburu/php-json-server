<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Exceptions\NotFoundEntityException;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Delete extends HttpMethod
{
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
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
}
