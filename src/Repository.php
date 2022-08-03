<?php

declare(strict_types=1);

namespace JsonServer;

use JsonServer\Exceptions\NotFoundEntityException;

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

    public function save(array $data): array
    {
        $data = ['id' => $this->nextId()] + $data;

        array_push($this->entityData, $data);

        $this->database->save($this->entityName, $this->entityData);

        return $data;
    }

    public function update(int $id, $data): array
    {
        $pos = array_search($id, array_column($this->entityData, 'id'), true);
        
        if ($pos === false) {
            throw new NotFoundEntityException();
        }
        $data = ['id' => $id] + $data;
        $this->entityData[$pos] = $data;
        
        $this->database->save($this->entityName, $this->entityData);
        
        return $data;
    }
    
    public function delete(int $id): void
    {
        $pos = array_search($id, array_column($this->entityData, 'id'), true);
        
        if ($pos === false) {
            throw new NotFoundEntityException();
        }

        unset($this->entityData[$pos]);
        $this->entityData = array_values($this->entityData);
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
