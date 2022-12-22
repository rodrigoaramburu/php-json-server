<?php

declare(strict_types=1);

namespace JsonServer\Command\Start;

use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    private string $port = '8000';
    private string $address = 'localhost';

    public function handle(): void
    {
        if (! function_exists('proc_open')) {
            $this->getPrinter()->error('function proc_open is disabled');
            exit;
        }
        $this->port = $this->input->params['port'] ?? '8000';
        $this->address = $this->input->params['address'] ?? 'localhost';

        $this->getPrinter()->info('Iniciando Servidor...');
        $this->getPrinter()->newline();

        $env = [
            'DATA_DIR' => $this->input->params['data-dir'] ?? getcwd(),
            'USE_STATIC_ROUTE' => in_array('--use-static-route', $this->input->flags) ? 'true' : 'false',
        ];

        $cwd = $this->input->params['root_package']."/bin/";
        $this->execShellCommand("php -S $this->address:$this->port cliserver.php", $env, $cwd, $this->outputHandler(...));
    }

    public function outputHandler(array $pipes)
    {
        $this->getPrinter()->display("Servidor rodando em http://$this->address:$this->port");
            $this->getPrinter()->newline();
            while (!feof($pipes[2])) {
                $this->processOutput(fgets($pipes[2]));
            }
    }

    protected function execShellCommand(string $command, array $env, ?string $cwd, callable $outputHandle): void
    {
        $proc = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $cwd,
            $env
        );
        $outputHandle($pipes);
    }

    protected function processOutput(string $output): void
    {
        if (str_contains($output, ']:')) {
            preg_match('/\[(.+)\] \d+\.\d+\.\d+\.\d+\:\d+ \[(\d+)\]: (\w+) (.+)/', $output, $matches);

            list($f, $date, $status, $method, $path) = $matches;

            $status= $status < 400 ? "<success>$status</success>" : "<error>$status</error>";
            $date = date('Y-m-d H:i:s', strtotime($date));
            $this->getPrinter()->out("$date <alt>$method</alt> $status - $path");
            $this->getPrinter()->newline();
        }
    }
}
