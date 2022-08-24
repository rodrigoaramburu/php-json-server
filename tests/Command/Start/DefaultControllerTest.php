<?php

declare(strict_types=1);

use JsonServer\Command\Start\DefaultController;
use Minicli\App;
use Minicli\Command\CommandCall;

beforeEach(function () {
    $config = [
        'app_path' => __DIR__.'/../../../src/Command/',
    ];

    $this->commandApp = new App($config);
});

test('should call execShell', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command): string
        {
            expect($command)->toBe('DATA_DIR='.getcwd().' USE_STATIC_ROUTE=false php -S localhost:8000 ./bin/build-in-server.php');

            return '';
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');

test('should receive the json dir', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command): string
        {
            expect($command)->toBe('DATA_DIR=new-data USE_STATIC_ROUTE=false php -S localhost:8000 ./bin/build-in-server.php');

            return '';
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.', 'data-dir=new-data']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');

test('should flag use of static middleware', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command): string
        {
            expect($command)->toBe('DATA_DIR='.getcwd().' USE_STATIC_ROUTE=true php -S localhost:8000 ./bin/build-in-server.php');

            return '';
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.', '--use-static-route']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');

test('should change the port', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command): string
        {
            expect($command)->toBe('DATA_DIR='.getcwd().' USE_STATIC_ROUTE=false php -S localhost:4321 ./bin/build-in-server.php');

            return '';
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.', 'port=4321']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');
