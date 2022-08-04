<?php

declare(strict_types=1);

namespace JsonServer;

use Doctrine\Inflector\InflectorFactory;

class Query
{
    public function __construct(
        private array $data
    ) {
    }

    public function get(): array
    {
        return $this->data;
    }

    public function find(int $id): ?array
    {
        $data = array_filter($this->data, function ($data) use ($id) {
            return $data['id'] == $id;
        });

        $data = reset($data);

        if (! $data) {
            return null;
        }

        return $data;
    }

    public function whereParent(string $entityName, int $id): Query
    {
        $inflector = InflectorFactory::create()->build();
        $column = $inflector->singularize($entityName).'_id';

        $data = array_filter($this->data, function ($entity) use ($column, $id) {
            return $entity[$column] === $id;
        });
        $data = array_values($data);

        return new Query($data);
    }
}
