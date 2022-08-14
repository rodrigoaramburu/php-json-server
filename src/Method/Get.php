<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use JsonServer\Exceptions\NotFoundResourceRepositoryException;

class Get extends HttpMethod
{
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
    {
        $query = $this->database->from($parsedUri->currentResource->name)->query();

        if ($parsedUri->currentResource->parent !== null) {
            $query = $query
                        ->whereParent(
                            resourceName: $parsedUri->currentResource->parent->name,
                            id: $parsedUri->currentResource->parent->id
                        );
        }

        $params = $request->getQueryParams();
        foreach ($params as $key => $param) {
            $query = $query->where($key, $param);
        }

        if ($parsedUri->currentResource->id === null) {
            $data = $query->get();
        } else {
            $data = $query->find($parsedUri->currentResource->id);
            if ($data === null) {
                throw new NotFoundResourceRepositoryException();
            }
        }

        $bodyResponse = $this->psr17Factory->createStream(json_encode($data));

        return $response->withBody($bodyResponse);
    }
}
