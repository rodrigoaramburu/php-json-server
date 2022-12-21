<?php

declare(strict_types=1);

namespace JsonServer\Utils;

use ErrorException;

final class JsonFileWriter
{
    private mixed $fileDb = null;

    public function __construct(private string $mode = 'a+b')
    {
    }

    public function loadOrCreateFile(string $filename): array
    {
        $dir = dirname($filename);
        if (! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        return $this->loadFile($filename);
    }

    public function loadFile(string $filename): array
    {
        try {
            $this->fileDb = fopen($filename, $this->mode);
            if (!$this->fileDb) {
                throw new \RuntimeException("cannot open file $filename");
            }
            flock($this->fileDb, LOCK_EX);

            $jsonString = filesize($filename) > 0 ? fread($this->fileDb, filesize($filename)) : '{}';

            if ($jsonString === null) {
                throw new \InvalidArgumentException('data is not a JSON string');
            }
            return json_decode($jsonString, true);
        } catch(ErrorException $e) {
            throw new \RuntimeException("cannot open file $filename");
        }
    }

    public function writeFile(array $data): void
    {
        ftruncate($this->fileDb, 0);
        rewind($this->fileDb);
        fwrite($this->fileDb, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }

    public function __destruct()
    {
        if ($this->fileDb) {
            flock($this->fileDb, LOCK_UN);
        }
    }
}
