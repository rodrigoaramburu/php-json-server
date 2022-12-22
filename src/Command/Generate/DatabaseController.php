<?php

declare(strict_types=1);

namespace JsonServer\Command\Generate;

use JsonServer\Utils\JsonFile;
use Minicli\Command\CommandController;

class DatabaseController extends CommandController
{
    private jsonFile $jsonFile;

    public function __construct()
    {
        $this->jsonFile = new JsonFile();
    }

    public function handle(): void
    {
        $databaseFileName = $this->hasParam('filename') ? getcwd().'/'.$this->getParam('filename') : getcwd().'/database.json';

        $database = $this->jsonFile->loadOrCreateFile($databaseFileName);

        $resourcesNames = array_slice($this->getArgs(), 3);

        $database = $database + $this->initResourcesEmpty($resourcesNames);

        $database['embed-resources'] = $this->embedParse();

        $this->jsonFile->writeFile($database);

        $this->showResult($databaseFileName, $database);
    }

    public function embedParse(): array
    {
        if (! $this->hasParam('embed')) {
            return [];
        }

        $embedData = $this->getParam('embed');

        $tmp = explode(';', trim($embedData, '"'));
        $embed = [];
        foreach ($tmp as $p) {
            preg_match('/(.*)\[(.*)\]/', $p, $output_array);
            $embed[$output_array[1]] = explode(',', $output_array[2]);
        }

        return $embed;
    }


    private function showResult(string $databaseFileName, array $database): void
    {
        $this->getPrinter()->display("File created: <success>{$databaseFileName}</success>");
        $this->getPrinter()->display('with resources: ');
        foreach (array_keys($database) as $value) {
            if ($value === 'embed-resources') {
                continue;
            }
            $this->getPrinter()->out("\t<success>$value</success>");
            $this->getPrinter()->newline();
        }
        $this->getPrinter()->display('with relations: ');
        foreach ($database['embed-resources'] as $key => $value) {
            $this->getPrinter()->out("\t<success>$key = ".implode(', ', $value) . "</success>");
            $this->getPrinter()->newline();
        }

        $this->getPrinter()->newline();
        $this->getPrinter()->newline();
    }

    private function initResourcesEmpty(array $resourcesNames): array
    {
        return array_combine($resourcesNames, array_fill(0, count($resourcesNames), []));
    }
}
