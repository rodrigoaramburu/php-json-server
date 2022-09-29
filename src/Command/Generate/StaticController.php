<?php

declare(strict_types=1);

namespace JsonServer\Command\Generate;

use JsonServer\Utils\JsonFileTrait;
use Minicli\Command\CommandController;

class StaticController extends CommandController
{
    use JsonFileTrait;

    public function handle(): void
    {
        $staticFileName = $this->hasParam('filename') ? getcwd().'/'.$this->getParam('filename') : getcwd().'/static.json';
        $static = $this->loadFile($staticFileName);

        $static[$this->getPath()] = [
            $this->getMethod() => [
                'body' => $this->getBody(),
                'statusCode' => $this->getStatusCode(),
                'headers' => $this->getHeaders(),
            ],
        ];

        $this->writeFile($static);
        $this->getPrinter()->out("the data has write in <success>{$this->filename}</success>");
    }

    public function getPath(): string
    {
        if ($this->hasParam('path')) {
            return trim($this->getParam('path'), '"');
        }

        $path = $this->getApp()->question->question('Path of the route:');
        if (empty($path)) {
            $this->getPrinter()->error('path cannot by empty');
            exit;
        }

        return $path;
    }

    public function getMethod(): string
    {
        if ($this->hasParam('method')) {
            return trim($this->getParam('method'), '"');
        }

        $method = $this->getApp()->question->question('Method of the route:', 'GET');

        return $method;
    }

    public function getBody(): string
    {
        if ($this->hasParam('body')) {
            return trim($this->getParam('body'), '"');
        }

        $body = $this->getApp()->question->question('Body of the route:', '');

        return $body;
    }

    public function getStatusCode(): string
    {
        if ($this->hasParam('statusCode')) {
            return trim($this->getParam('statusCode'), '"');
        }

        $statusCode = $this->getApp()->question->question('Status Code of the route:', '200');

        return $statusCode;
    }

    public function getHeaders(): array
    {
        if ($this->hasParam('headers')) {
            $headersParam = trim($this->getParam('headers'), '"');
            if (empty($headersParam)) {
                return [];
            }

            parse_str($headersParam, $headers);

            return $headers;
        }

        $headers = [];
        $this->getPrinter()->info('Empty name will end.', true);
        $this->getPrinter()->newline();
        do {
            $headerName = $this->getApp()->question->question('Header Name:', '');
            $this->getPrinter()->newLine();

            if (empty($headerName)) {
                break;
            }
            $value = $this->getApp()->question->question("Header <info>$headerName</info> value:");
            $this->getPrinter()->newLine();

            $headers[$headerName] = $value;
        } while (! empty($headerName));

        return $headers;
    }
}
