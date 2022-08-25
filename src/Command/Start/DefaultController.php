<?php

declare(strict_types=1);

namespace JsonServer\Command\Start;

use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $this->getPrinter()->info('Iniciando Servidor...');

        $params = [
            'DATA_DIR' => $this->input->params['data-dir'] ?? getcwd(),
            'USE_STATIC_ROUTE' => in_array('--use-static-route', $this->input->flags) ? 'true' : 'false',
        ];

        $this->startBuildInServer($params);
    }

    private function startBuildInServer(array $params): void
    {
        $env = array_reduce(array_keys($params), function ($carry, $key) use ($params) {
            $carry .= "$key={$params[$key]} ";

            return $carry;
        });

        $port = $this->input->params['port'] ?? '8000';
        $root_package = $this->input->params['root_package'];
        $this->execShellCommand($env."php -S localhost:$port ".$root_package.'/bin/build-in-server.php');
    }

    protected function execShellCommand(string $command): string
    {
        if (! function_exists('exec')) {
            $this->getPrinter()->error('function exec is disabled');
            exit;
        }

        return exec($command);
    }
}
