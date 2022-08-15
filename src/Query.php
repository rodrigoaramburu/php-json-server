<?php

declare(strict_types=1);

namespace JsonServer;

use Doctrine\Inflector\InflectorFactory;

class Query
{
    public const ORDER_ASC = 'ASC';

    public const ORDER_DESC = 'DESC';

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

    public function whereParent(string $resourceName, int $id): Query
    {
        $inflector = InflectorFactory::create()->build();
        $column = $inflector->singularize($resourceName).'_id';

        $data = array_filter($this->data, function ($resource) use ($column, $id) {
            return $resource[$column] === $id;
        });
        $data = array_values($data);

        return new Query($data);
    }

    public function where(string $field, string $value): Query
    {
        $data = array_filter($this->data, function ($resource) use ($field, $value) {
            return str_contains(
                strtolower($resource[$field]),
                strtolower($value)
            );
        });

        return new Query(array_values($data));
    }

    public function orderBy(string $field, string $order = 'ASC'): Query
    {
        usort($this->data, function (array $a, array $b) use ($field, $order) {
            if ($a[$field] == $b[$field]) {
                return 0;
            }
            if ($order == self::ORDER_ASC) {
                return ($a[$field] < $b[$field]) ? -1 : 1;
            }

            return ($a[$field] > $b[$field]) ? -1 : 1;
        });

        return new Query($this->data);
    }
}
