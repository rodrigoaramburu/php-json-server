<?php

declare(strict_types=1);

namespace JsonServer;

use InvalidArgumentException;
use JsonServer\Exceptions\NotFoundEntityRepositoryException;

class Database
{
    private $fileDb;

    private array $entities = [];

    public function __construct(string $dbFileJson = 'db.json')
    {
        $this->fileDb = fopen($dbFileJson, 'r+b');

        if (! $this->fileDb) {
            throw new \RuntimeException("cannot open file $dbFileJson");
        }
        flock($this->fileDb, LOCK_EX);
        $jsonString = filesize($dbFileJson) > 0 ? fread($this->fileDb, filesize($dbFileJson)) : null;
        if ($jsonString !== null) {
            $entities = json_decode($jsonString, true);

            if (is_array($entities)) {
                foreach ($entities as $key => $entity) {
                    $this->entities[$key] = $entity;
                }
            }
        } else {
            throw new InvalidArgumentException('data is not a JSON string');
        }
    }

    public function __destruct()
    {
        flock($this->fileDb, LOCK_UN);
    }

    public function from(string $entityName): Repository
    {
        if (! array_key_exists($entityName, $this->entities)) {
            throw new NotFoundEntityRepositoryException("entity $entityName not found");
        }

        return new Repository($entityName, $this->entities[$entityName], $this);
    }

    public function save(string $entityName, array $data): void
    {
        $this->entities[$entityName] = $data;
        ftruncate($this->fileDb, 0);
        rewind($this->fileDb);
        fwrite($this->fileDb, json_encode($this->entities, JSON_PRETTY_PRINT));
    }
}
