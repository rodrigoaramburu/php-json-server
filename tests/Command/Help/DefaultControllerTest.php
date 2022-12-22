<?php

declare(strict_types=1);

use JsonServer\Command\Help\DefaultController;
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

test('should show command list', function () {
    $helpCommand = new DefaultController();

    $helpCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'help', 'database', 'posts', 'comments']);

    $helpCommand->run($input);
})->expectOutputRegex('/Comando Disponíveis/');