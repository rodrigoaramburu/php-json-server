<?php

declare(strict_types=1);

namespace JsonServer;

use ErrorException;
use InvalidArgumentException;
use JsonServer\Exceptions\NotFoundResourceRepositoryException;

class Database
{
    private $fileDb;

    private array $resources = [];

    public function __construct(string $databasefile = 'database.json')
    {
        try {
            $this->fileDb = fopen($databasefile, 'r+b');

            flock($this->fileDb, LOCK_EX);
            $jsonString = filesize($databasefile) > 0 ? fread($this->fileDb, filesize($databasefile)) : null;
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
        } catch (ErrorException $e) {
            throw new \RuntimeException("cannot open file $databasefile");
        }
    }

    public function __destruct()
    {
        flock($this->fileDb, LOCK_UN);
    }

    public function from(string $resourceName): Repository
    {
        if (! array_key_exists($resourceName, $this->resources) || str_starts_with($resourceName, '_')) {
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

    public function embedResources(): array
    {
        return $this->resources['embed-resources'] ?? [];
    }
}
