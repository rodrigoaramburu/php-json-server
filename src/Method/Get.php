<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Query;
use JsonServer\Utils\ParsedUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Get extends HttpMethod
{
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ParsedUri $parsedUri): ResponseInterface
    {
        $query = $this
                ->database()
                ->from($parsedUri->currentResource->name)
                ->query();

        $query = $this->filterParent($query, $parsedUri);

        $params = $request->getQueryParams();

        if ($parsedUri->currentResource->id === null) {
            $query = $this->filter($query, $params);

            $query = $this->order($query, $params);

            $data = $query->get();

            $data = $this->embedParent($data);
            $data = $this->embedChildren(resources: $data, resourceName: $parsedUri->currentResource->name);
        } else {
            $data = $query->find($parsedUri->currentResource->id);

            if ($data === null) {
                throw new NotFoundResourceException();
            }

            $data = $this->embedParent([$data])[0];
            $data = $this->embedChildren(resources: [$data], resourceName: $parsedUri->currentResource->name)[0];
        }

        $bodyResponse = $this->psr17Factory()->createStream(json_encode($data));

        return $response->withBody($bodyResponse);
    }

    private function filter(Query $query, array $params): Query
    {
        foreach ($params as $key => $param) {
            if (! str_starts_with($key, '_')) {
                $query = $query->where($key, $param);
            }
        }

        return $query;
    }

    private function order(Query $query, array $params): Query
    {
        foreach ($params as $key => $param) {
            if ($key == '_sort') {
                $query = $query->orderBy(
                    $param,
                    isset($params['_order']) ? strtoupper($params['_order']) : Query::ORDER_ASC
                );
            }
        }

        return $query;
    }

    private function filterParent(Query $query, ParsedUri $parsedUri): Query
    {
        if ($parsedUri->currentResource->parent !== null) {
            $query = $query
                        ->whereParent(
                            resourceName: $parsedUri->currentResource->parent->name,
                            id: $parsedUri->currentResource->parent->id
                        );
        }

        return $query;
    }
}
