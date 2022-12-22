<?php

declare(strict_types=1);

use JsonServer\Command\Start\DefaultController;
use JsonServer\Utils\TagFilter;
use Minicli\App;
use Minicli\Command\CommandCall;

beforeEach(function () {
    $config = [
        'app_path' => __DIR__.'/../../../src/Command/',
    ];

    $this->commandApp = new App($config);
    $this->commandApp->getPrinter()->registerFilter(new TagFilter());
});

test('should call execShell', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command, array $env, ?string $cwd, callable $outputHandler): void
        {
            expect($command)->toBe('php -S localhost:8000 cliserver.php');
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');

test('should receive the json dir', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command, array $env, ?string $cwd, callable $outputHandler): void
        {
            expect($command)->toBe('php -S localhost:8000 cliserver.php');
            expect($env)->toBe([
                'DATA_DIR' => 'new-data',
                'USE_STATIC_ROUTE' => 'false'
            ]);
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.', 'data-dir=new-data']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');

test('should flag use of static middleware', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command, array $env, ?string $cwd, callable $outputHandler): void
        {
            expect($command)->toBe('php -S localhost:8000 cliserver.php');
            expect($env)->toMatchArray([
                'USE_STATIC_ROUTE' => 'true'
            ]);
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.', '--use-static-route']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');

test('should change the port', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command, array $env, ?string $cwd, callable $outputHandler): void
        {
            expect($command)->toBe('php -S localhost:4321 cliserver.php');
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.', 'port=4321']);

    $startCommand->run($input);
})->expectOutputRegex('/Iniciando Servidor.../');


test('should process build-in server output', function () {
    $startCommand = new class () extends DefaultController {
        protected function execShellCommand(string $command, array $env, ?string $cwd, callable $outputHandler): void
        {
            parent::processOutput('[Mon Oct 10 14:06:18 2022] 127.0.0.1:53144 [200]: GET /teste.php');
        }
    };

    $startCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'start', 'root_package=.']);

    ob_start();
    $startCommand->run($input);
    $content = ob_get_clean();

    expect($content)->toContain("2022-10-10 14:06:18 " . s('alt'). "GET" .s('default') . " " . s('success') . '200' . s('default') . " - /teste.php");
});

test('should execute shell command', function () {
    $startCommand = new class () extends DefaultController {
        public function execShellCommand(string $command, array $env, ?string $cwd, callable $outputHandler): void
        {
            parent::execShellCommand($command, $env, null, $outputHandler);
        }
    };

    $startCommand->execShellCommand("echo 'teste'", [], null, function ($pipes) {
        expect(fgets($pipes[1]))->toBe("teste\n");
    });
});


test('should handle the output from process', function(){
    $startCommand = new DefaultController();
    $startCommand->boot($this->commandApp);

    $stream1 = fopen('php://memory','r+');
    
    $stream2 = fopen('php://memory','w+');
    $stream3 = fopen('php://memory','w+');
    fwrite($stream3, "[Mon Oct 10 14:06:18 2022] 127.0.0.1:53144 [200]: GET /teste.php");
    rewind($stream3);

    ob_start();
    $startCommand->outputHandler([
        $stream1,
        $stream2,
        $stream3,
    ]);
    $contents = ob_get_contents();
    ob_end_clean();
    expect($contents)->toContain("Servidor rodando em http://localhost:8000");
    expect($contents)->toContain("2022-10-10 14:06:18 " . s('alt'). "GET" .s('default') . " " . s('success') . '200' . s('default') . " - /teste.php");

});
