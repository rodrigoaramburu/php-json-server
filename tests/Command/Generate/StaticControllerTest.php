<?php

declare(strict_types=1);

use JsonServer\Command\Generate\StaticController;
use JsonServer\Utils\Question;
use JsonServer\Utils\Reader;
use Minicli\App;
use Minicli\Command\CommandCall;
use Pest\Mock\Mock;

beforeEach(function () {
    $config = [
        'app_path' => __DIR__.'/../../../src/Command/',
    ];

    $this->commandApp = new App($config);
    /** Reader|Mock */
    $this->questionMock = Mockery::mock(Question::class);
    $this->questionMock->shouldReceive('load');
    $this->commandApp->addService('question', $this->questionMock);
    chdir(__DIR__.'/../../tmp/');
});

afterEach(function () {
    $files = [
        getcwd().'/static.json',
    ];
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('should generate static.json with values from param', function () {
    $filename = getcwd().'/static.json';

    $staticCommand = new StaticController();
    $staticCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'static',
        'path="/routes/static"',
        'method="GET"',
        'body="body response"',
        'headers="header1=value1&header2=value2"',
        'statusCode="201"',
    ]);

    $staticCommand->run($input);

    $staticData = json_decode(file_get_contents($filename), true);
    expect($staticData)->toMatchArray(
        [
            '/routes/static' => [
                'GET' => [
                    'body' => 'body response',
                    'statusCode' => '201',
                    'headers' => [
                        'header1' => 'value1',
                        'header2' => 'value2',
                    ],
                ],
            ],
        ]
    );
})->expectOutputRegex('/.*/');

test('should ask values to generate static.json', function () {
    $filename = getcwd().'/static.json';

    $staticCommand = new StaticController();
    $staticCommand->boot($this->commandApp);

    $this->questionMock->shouldReceive('question')->andReturn(
        '/routes/static',
        'GET',
        'body response',
        '201',
        'header1',
        'value1',
        'header2',
        'value2',
        ''
    );

    $input = new CommandCall([
        'json-server',
        'generate',
        'static',
    ]);

    $staticCommand->run($input);

    $staticData = json_decode(file_get_contents($filename), true);
    expect($staticData)->toMatchArray(
        [
            '/routes/static' => [
                'GET' => [
                    'body' => 'body response',
                    'statusCode' => '201',
                    'headers' => [
                        'header1' => 'value1',
                        'header2' => 'value2',
                    ],
                ],
            ],
        ]
    );
})->expectOutputRegex('/.*/');

test('should generate static.json with empty header if param header empty', function () {
    $filename = getcwd().'/static.json';

    $staticCommand = new StaticController();
    $staticCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'static',
        'path="/routes/static"',
        'method="GET"',
        'body="body response"',
        'headers=""',
        'statusCode="201"',
    ]);

    $staticCommand->run($input);

    $staticData = json_decode(file_get_contents($filename), true);
    expect($staticData)->toMatchArray(
        [
            '/routes/static' => [
                'GET' => [
                    'body' => 'body response',
                    'statusCode' => '201',
                    'headers' => [],
                ],
            ],
        ]
    );
})->expectOutputRegex('/.*/');
