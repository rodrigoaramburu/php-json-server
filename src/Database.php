<?php

declare(strict_types=1);

namespace JsonServer;

use InvalidArgumentException;
use JsonServer\Exceptions\NotFoundResourceRepositoryException;

class Database
{
    private $fileDb;

    private array $resources = [];

    public function __construct(string $dbFileJson = 'db.json')
    {
        $this->fileDb = fopen($dbFileJson, 'r+b');

        if (! $this->fileDb) {
            throw new \RuntimeException("cannot open file $dbFileJson");
        }
        flock($this->fileDb, LOCK_EX);
        $jsonString = filesize($dbFileJson) > 0 ? fread($this->fileDb, filesize($dbFileJson)) : null;
        if ($jsonString !== null) {
            $resources = json_decode($jsonString, true);

            if (is_array($resources)) {
                foreach ($resources as $key => $resource) {
                    $this->resources[$key] = $resource;
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

    public function from(string $resourceName): Repository
    {
        if (! array_key_exists($resourceName, $this->resources)) {
            throw new NotFoundResourceRepositoryException("resource $resourceName not found");
        }

        return new Repository($resourceName, $this->resources[$resourceName], $this);
    }

    public function save(string $resourceName, array $data): void
    {
        $this->resources[$resourceName] = $data;
        ftruncate($this->fileDb, 0);
        rewind($this->fileDb);
        fwrite($this->fileDb, json_encode($this->resources, JSON_PRETTY_PRINT));
    }
}
