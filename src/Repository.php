<?php

declare(strict_types=1);

namespace JsonServer;

use JsonServer\Exceptions\NotFoundResourceException;

class Repository
{
    public function __construct(
        private string $resourceName,
        private array $resourceData,
        private Database $database
    ) {
    }

    public function get(): array
    {
        return $this->resourceData;
    }

    public function find(int $id): ?array
    {
        $data = array_filter($this->resourceData, function ($data) use ($id) {
            return $data['id'] == $id;
        });

        $data = reset($data);

        if (! $data) {
            return null;
        }

        return $data;
    }

    public function save(array $data, $parentresourceName = null, int $parentId = 0): array
    {
        $data = ['id' => $this->nextId()] + $data;

        array_push($this->resourceData, $data);

        $this->database->save($this->resourceName, $this->resourceData);

        return $data;
    }

    public function update(int $id, $data): array
    {
        $pos = array_search($id, array_column($this->resourceData, 'id'), true);

        if ($pos === false) {
            throw new NotFoundResourceException();
        }
        $data = ['id' => $id] + $data;
        $this->resourceData[$pos] = $data;

        $this->database->save($this->resourceName, $this->resourceData);

        return $data;
    }

    public function delete(int $id): void
    {
        $pos = array_search($id, array_column($this->resourceData, 'id'), true);

        if ($pos === false) {
            throw new NotFoundResourceException();
        }

        unset($this->resourceData[$pos]);
        $this->resourceData = array_values($this->resourceData);
        $this->database->save($this->resourceName, $this->resourceData);
    }

    public function query(): Query
    {
        return new Query($this->resourceData);
    }

    private function nextId(): int
    {
        $ids = array_column($this->resourceData, 'id');

        if (count($ids) === 0) {
            return 1;
        }

        return max($ids) + 1;
    }
}
