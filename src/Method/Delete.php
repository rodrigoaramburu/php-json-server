<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Delete extends HttpMethod
{
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
    {
        if ($parsedUri->currentResource->id === null) {
            throw new NotFoundResourceException();
        }

        $repository = $this->database->from($parsedUri->currentResource->name);

        if ($parsedUri->currentResource->parent !== null) {
            $parentData = $this->database
                ->from($parsedUri->currentResource->parent->name)
                   ->find($parsedUri->currentResource->parent->id);

            $column = $this->inflector->singularize($parsedUri->currentResource->parent->name).'_id';
            $parentIdFromResource = $repository->find($parsedUri->currentResource->id)[$column];

            if ($parentData === null || $parentIdFromResource !== $parsedUri->currentResource->parent->id) {
                throw new NotFoundResourceException();
            }
        }

        $repository->delete($parsedUri->currentResource->id);

        return $response->withStatus(204);
    }
}
