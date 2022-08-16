<?php

declare(strict_types=1);

namespace JsonServer\Method;

use JsonServer\Exceptions\NotFoundResourceRepositoryException;
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
                throw new NotFoundResourceRepositoryException();
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

    private function embedParent($resources): array
    {
        for ($i = 0; $i < count($resources); $i++) {
            $keys = array_filter(array_keys($resources[$i]), fn ($key) => str_ends_with($key, '_id'));

            foreach ($keys as $key) {
                $field = str_replace('_id', '', $key);
                $resourceName = $this->inflector()->pluralize($field);
                $resourceParent = $this->database()->from($resourceName)->find($resources[$i][$key]);
                $resources[$i][$field] = $resourceParent;
                unset($resources[$i][$key]);
            }
        }

        return $resources;
    }

    private function embedChildren($resources, $resourceName): array
    {
        $resourceNameFieldId = $this->inflector()->singularize($resourceName).'_id';
        $childrenResourceNames = $this->database()->embedResources()[$resourceName] ?? [];
        for ($i = 0; $i < count($resources); $i++) {
            foreach ($childrenResourceNames as $childResourceName) {
                $resources[$i][$childResourceName] = $this->database()
                    ->from($childResourceName)
                    ->query()
                    ->where($resourceNameFieldId, (string) $resources[$i]['id'])
                    ->get();
                $resources[$i][$childResourceName] = array_map(function ($resource) use ($resourceNameFieldId) {
                    unset($resource[$resourceNameFieldId]);

                    return $resource;
                }, $resources[$i][$childResourceName]);
            }
        }

        return $resources;
    }
}
