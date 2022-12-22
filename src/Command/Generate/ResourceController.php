<?php

declare(strict_types=1);

namespace JsonServer\Command\Generate;

use Faker\Factory;
use BadMethodCallException;
use InvalidArgumentException;
use JsonServer\Utils\JsonFile;
use Minicli\Command\CommandController;

class ResourceController extends CommandController
{
    private JsonFile $jsonFile;

    public function __construct($faker = null)
    {
        if ($faker === null) {
            $this->faker = Factory::create();
        } else {
            $this->faker = $faker;
        }
        $this->jsonFile = new JsonFile();
    }

    public function handle(): void
    {
        $databaseFileName = $this->hasParam('filename') ? getcwd().'/'.$this->getParam('filename') : getcwd().'/database.json';
        $database = $this->jsonFile->loadOrCreateFile($databaseFileName);

        $num = (int) ($this->getParam('num') ?? 1);
        $resourceName = $this->getArgs()[3] ?? null;
        if ($resourceName === null) {
            throw new InvalidArgumentException('resource name is missing');
        }
        $this->database[$resourceName] = $this->database[$resourceName] ?? [];

        $fields = ! $this->hasFlag('--it-fields') && $this->hasParam('fields')
                        ? $this->fieldsFromParam()
                        : $this->fieldsFromPrompt($resourceName);

        for ($i = 0; $i < $num; $i++) {
            $database[$resourceName][] = $this->generateResource($fields, $resourceName, $database);
        }

        $this->confirmWrite($resourceName, $num, $fields);

        $this->jsonFile->writeFile($database);
        $this->getPrinter()->display("the data has write in <success>{$databaseFileName}</success>");
    }

    private function generateResource(array $fieldOptions, string $resourceName, array $database): array
    {
        $resource = [];
        foreach ($fieldOptions as $field => $option) {
            $tmp = explode('.', $option);
            $function = $tmp[0];
            $params = array_slice($tmp, 1);

            if ($function == 'id') {
                $resource[$field] = $this->nextId($resourceName, $database);

                continue;
            }

            try {
                $resource[$field] = $this->faker->$function(...$params);
            } catch (BadMethodCallException $e) {
                throw new BadMethodCallException("generate field function $function not exists", 0, $e);
            }
        }

        return $resource;
    }

    private function fieldsFromParam(): array
    {
        $fieldsData = trim($this->getParam('fields'), '"');

        parse_str($fieldsData, $fields);
        return $fields;
    }

    private function fieldsFromPrompt(string $resourceName): array
    {
        $this->getPrinter()->display("inform the fields of resource <info>\"$resourceName\"</info>.");
        $this->getPrinter()->info('Empty name will end.', true);
        $this->getPrinter()->newline();
        $fields = [];
        $field = '';
        do {
            $field = $this->getApp()->question->question('Name of field:', '');
            $this->getPrinter()->newLine();

            if (empty($field)) {
                break;
            }

            $type = $this->getApp()->question->question("Type of field <info>$field</info>:", 'text');
            $this->getPrinter()->newLine();

            $fields[$field] = $type;
        } while (! empty($field));

        return $fields;
    }

    private function confirmWrite(string $resource, int $num, array $fields): void
    {
        $this->getPrinter()->display("Resource: <success>$resource</success>");
        $this->getPrinter()->display("Number of resources: <success>$num</success>");
        $this->getPrinter()->display('Fields: ');
        foreach ($fields as $key => $value) {
            $this->getPrinter()->out("<success>$key</success> \t => \t <success>$value</success>");
            $this->getPrinter()->newline();
        }

        $confirm = $this->getApp()->question->confirmation('Do you confirm generation? ', true, '/^(y|s)/i', ['y', 'n']);
        if (! $confirm) {
            $this->getPrinter()->error('Command aborted', true);
            exit;
        }
    }

    private function nextId(string $resourceName, array $database): int
    {
        $ids = array_column($database[$resourceName], 'id');

        if (count($ids) === 0) {
            return 1;
        }

        return max($ids) + 1;
    }
}
