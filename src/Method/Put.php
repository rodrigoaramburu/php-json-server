<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Exceptions\NotFoundEntityException;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Put extends HttpMethod
{
    public function execute(RequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
    {
        if ($parsedUri->currentEntity->id === null) {
            throw new NotFoundEntityException('entity not found');
        }
        $repository = $this->database->from($parsedUri->currentEntity->name);

        $entityData = $this->bodyDecode((string) $request->getBody());

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
            $data = $repository->save(json_decode((string) $request->getBody(), true));
        }

        $bodyResponse = $this->psr17Factory->createStream(json_encode($data));

        return $response
            ->withStatus($statusCode)
            ->withBody($bodyResponse);
    }
}
