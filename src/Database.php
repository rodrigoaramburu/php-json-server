<?php

declare(strict_types=1);

namespace JsonServer;

use JsonServer\Utils\JsonFileWriter;
use JsonServer\Exceptions\NotFoundResourceRepositoryException;

class Database
{
    private $fileDb;

    private array $resources = [];

    private JsonFileWriter $jsonFileWriter;

    public function __construct(string $databaseFile = 'database.json')
    {
        $this->jsonFileWriter = new JsonFileWriter(mode: 'r+b');
        $resources = $this->jsonFileWriter->loadFile($databaseFile);
        if (is_array($resources)) {
            foreach ($resources as $key => $resource) {
                $this->resources[$key] = $resource;
            }
        }
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
        $this->jsonFileWriter->writeFile($this->resources);
    }

    public function embedResources(): array
    {
        return $this->resources['embed-resources'] ?? [];
    }
}
