<?php

declare(strict_types=1);

use JsonServer\Command\Generate\DatabaseController;
use Minicli\App;
use Minicli\Command\CommandCall;

beforeEach(function () {
    $config = [
        'app_path' => __DIR__.'/../../../src/Command/',
    ];

    $this->commandApp = new App($config);
    if(!file_exists(__DIR__.'/../../tmp/')) mkdir(__DIR__.'/../../tmp/');
    chdir(__DIR__.'/../../tmp/');
});

afterEach(function () {
    $files = [
        getcwd().'/database.json',
        getcwd().'/data/generate-database.json',
    ];
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    if(file_exists(__DIR__.'/../../tmp/data')) rmdir(__DIR__.'/../../tmp/data');
    if(file_exists(__DIR__.'/../../tmp/')) rmdir(__DIR__.'/../../tmp/');
});

test('should generate file with resources in current dir', function () {
    $databaseCommand = new DatabaseController();

    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'generate', 'database', 'posts', 'comments']);

    $databaseCommand->run($input);

    $filename = getcwd().'/database.json';
    $data = json_decode(file_get_contents($filename), true);

    expect($data)->toMatchArray([
        'posts' => [],
        'comments' => [],
    ]);
})->expectOutputRegex('/.*/');

test('should generate file with resource in specified dir', function () {
    $databaseCommand = new DatabaseController();

    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall(['json-server', 'generate', 'database', 'filename=data/generate-database.json', 'posts', 'comments']);

    $databaseCommand->run($input);

    expect(file_exists('data/generate-database.json'))->toBeTrue();
})->expectOutputRegex('/.*/');

test('should generate embed config by param', function () {
    $databaseCommand = new DatabaseController();

    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'database',
        'resource1',
        'resource2',
        'resource3',
        'embed="resource1[resource2,resource3];resource2[resource3]"',
    ]);

    $databaseCommand->run($input);

    $filename = getcwd().'/database.json';
    $data = json_decode(file_get_contents($filename), true);

    expect($data['embed-resources'])->toMatchArray([
        'resource1' => ['resource2', 'resource3'],
        'resource2' => ['resource3'],
    ]);
})->expectOutputRegex('/.*/');
