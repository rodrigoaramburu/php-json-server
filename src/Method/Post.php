<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Post extends HttpMethod
{
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
    {
        $repository = $this->database()->from($parsedUri->currentResource->name);

        $data = $this->bodyDecode((string) $request->getBody());

        $data = $this->includeParent($data, $parsedUri);

        $data = $repository->save(data: $data);

        $bodyResponse = $this->psr17Factory()->createStream(json_encode($data));

        return $response
                ->withStatus(201)
                ->withBody($bodyResponse);
    }
}
