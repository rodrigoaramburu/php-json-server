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
                'headers' => $this->getHeaders()
            ]
        ];


        $this->writeFile($static);
        $this->getPrinter()->info("the data has write in {$this->filename}");
    }

    public function getPath(): string
    {
        if ($this->hasParam('path')) {
            return trim($this->getParam('path'),'"');
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
            $headersExplode = explode('|', trim($this->getParam('headers') ,'"'));

            $headers = [];
            for($i = 0; $i< count($headersExplode); $i +=2) {
                $headers[$headersExplode[$i]] = $headersExplode[$i+1];
            }
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
            $fieldHighlight = $this->getPrinter()->filterOutput($headerName, 'info');
            $value = $this->getApp()->question->question("Header $fieldHighlight value:");
            $this->getPrinter()->newLine();

            $headers[$headerName] = $value;
        } while (! empty($headerName));

        return $headers;
    }
}
