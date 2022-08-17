<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Put extends HttpMethod
{
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
    {
        if ($parsedUri->currentResource->id === null) {
            throw new NotFoundResourceException();
        }
        $repository = $this->database()->from($parsedUri->currentResource->name);

        $resourceData = $this->bodyDecode((string) $request->getBody());

        if ($parsedUri->currentResource->parent !== null) {
            $column = $this->inflector()->singularize($parsedUri->currentResource->parent->name).'_id';
            if (! array_key_exists($column, $resourceData)) {
                $resourceData = $this->includeParent($resourceData, $parsedUri);
            }
        }

        try {
            $data = $repository->update(
                $parsedUri->currentResource->id,
                $resourceData
            );
            $statusCode = 200;
        } catch (NotFoundResourceException $e) {
            $statusCode = 201;
            $data = $repository->save(json_decode((string) $request->getBody(), true));
        }

        $data = $this->embedParent([$data])[0];
        $bodyResponse = $this->psr17Factory()->createStream(json_encode($data));

        return $response
            ->withStatus($statusCode)
            ->withBody($bodyResponse);
    }
}
