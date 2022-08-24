<?php

declare(strict_types=1);

namespace JsonServer\Utils;

trait JsonFileTrait
{
    private string $filename;

    private mixed $fileDb = null;

    protected function loadFile(string $filename): array
    {
        $this->filename = $filename;
        $dir = dirname($this->filename);
        if (! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->fileDb = fopen($this->filename, 'a+b');
        flock($this->fileDb, LOCK_EX);

        $jsonString = filesize($this->filename) > 0 ? fread($this->fileDb, filesize($this->filename)) : '{}';
        $data = json_decode($jsonString, true);

        return $data;
    }

    protected function writeFile(array $data): void
    {
        ftruncate($this->fileDb, 0);
        rewind($this->fileDb);
        fwrite($this->fileDb, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function __destruct()
    {
        if ($this->fileDb) {
            flock($this->fileDb, LOCK_UN);
        }
    }
}
