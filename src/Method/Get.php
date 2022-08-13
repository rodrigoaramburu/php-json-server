<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Exceptions\NotFoundEntityRepositoryException;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Get extends HttpMethod
{
    public function execute(RequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
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
}
