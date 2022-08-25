<?php

declare(strict_types=1);

namespace JsonServer\Command\Generate;

use JsonServer\Utils\JsonFileTrait;
use Minicli\Command\CommandController;

class DatabaseController extends CommandController
{
    use JsonFileTrait;

    public function handle(): void
    {
        $databaseFileName = $this->hasParam('filename') ? getcwd().'/'.$this->getParam('filename') : getcwd().'/database.json';
        $database = $this->loadFile($databaseFileName);

        $resourcesNames = array_slice($this->getArgs(), 3);

        $database = $database + array_combine($resourcesNames, array_fill(0, count($resourcesNames), []));

        $database['embed-resources'] = $this->embedParse();

        $this->writeFile($database);

        $this->getPrinter()->newline();
        $this->getPrinter()->out('File created: ');
        $this->getPrinter()->out($databaseFileName, 'success');
        $this->getPrinter()->newline();
        $this->getPrinter()->out('with resources: ');
        $this->getPrinter()->newline();
        foreach ($resourcesNames as $value) {
            $this->getPrinter()->out("\t".$value, 'success');
            $this->getPrinter()->newline();
        }
        $this->getPrinter()->newline();
        $this->getPrinter()->out('with relations: ');
        $this->getPrinter()->newline();
        foreach ($database['embed-resources'] as $key => $value) {
            $this->getPrinter()->out("\t".$key.' = '.implode(', ', $value), 'success');
            $this->getPrinter()->newline();
        }

        $this->getPrinter()->newline();
        $this->getPrinter()->newline();
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
}
