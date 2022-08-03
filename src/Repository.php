<?php

declare(strict_types=1);

namespace JsonServer;

class Repository
{
    public function __construct(
        private string $entityName,
        private array $entityData,
        private Database $database
    ) {
    }

    public function get(): array
    {
        return $this->entityData;
    }

    public function find(int $id): ?array
    {
        $data = array_filter($this->entityData, function ($data) use ($id) {
            return $data['id'] == $id;
        });

        $data = reset($data);

        if (! $data) {
            return null;
        }

        return $data;
    }

    public function save(array $data): void
    {
        $data = ['id' => $this->nextId()] + $data;

        array_push($this->entityData, $data);

        $this->database->save($this->entityName, $this->entityData);
    }

    private function nextId(): int
    {
        $ids = array_column($this->entityData, 'id');

        if (count($ids) === 0) {
            return 1;
        }

        return max($ids) + 1;
    }
}
