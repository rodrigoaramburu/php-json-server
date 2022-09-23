<?php

declare(strict_types=1);

namespace JsonServer\Command\Generate;

use BadMethodCallException;
use Faker\Factory;
use InvalidArgumentException;
use JsonServer\Utils\JsonFileTrait;
use Minicli\Command\CommandController;

class ResourceController extends CommandController
{
    use JsonFileTrait;

    public function __construct($faker = null)
    {
        if ($faker === null) {
            $this->faker = Factory::create();
        } else {
            $this->faker = $faker;
        }
    }

    public function handle(): void
    {
        $databaseFileName = $this->hasParam('filename') ? getcwd().'/'.$this->getParam('filename') : getcwd().'/database.json';
        $database = $this->loadFile($databaseFileName);

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

        $this->writeFile($database);
        $this->getPrinter()->info("the data has write in {$this->filename}");
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
        $fieldsData = $this->getParam('fields');

        $tmp = explode(';', trim($fieldsData, '"'));
        $fields = [];
        foreach ($tmp as $p) {
            $tmp2 = explode('.', $p);
            $fields[$tmp2[0]] = str_replace($tmp2[0].'.', '', $p);
        }

        return $fields;
    }

    private function fieldsFromPrompt(string $resourceName): array
    {
        $this->getPrinter()->info("inform the fields of resource the \"$resourceName\".");
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
            $fieldHighlight = $this->getPrinter()->filterOutput($field, 'info');
            $type = $this->getApp()->question->question("Type of field $fieldHighlight:", 'text');
            $this->getPrinter()->newLine();

            $fields[$field] = $type;
        } while (! empty($field));

        return $fields;
    }

    private function confirmWrite(string $resource, int $num, array $fields): void
    {
        if (! $this->hasFlag('--it-fields')) {
            return;
        }
        $this->getPrinter()->display("Resource: $resource");
        $this->getPrinter()->display("Number of resources: $num");
        $this->getPrinter()->display('Fields: ');
        foreach ($fields as $key => $value) {
            $this->getPrinter()->out($key."\t => \t".$value);
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
