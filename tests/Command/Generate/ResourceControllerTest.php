<?php

declare(strict_types=1);

use JsonServer\Command\Generate\ResourceController;
use JsonServer\Utils\Question;
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
        getcwd().'/database.json',
        getcwd().'/data/database.json',
    ];
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('should generate resource with title and author inside database file', function () {
    $filename = getcwd().'/database.json';

    /** @var \Faker\Generator|Mock */
    $fakerMock = mock(\Faker\Generator::class);

    $fakerMock->shouldReceive('sentence')->andReturn('the title');
    $fakerMock->shouldReceive('name')->andReturn('the author');

    $this->questionMock->shouldReceive('confirmation')->andReturn(true);

    $databaseCommand = new ResourceController($fakerMock);
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        'fields="title=sentence&author=name"'
    ]);

    $databaseCommand->run($input);

    $data = json_decode(file_get_contents($filename), true);
    expect($data['posts'])->toHaveCount(1);

    expect($data['posts'][0]['title'])->toBe('the title');
    expect($data['posts'][0]['author'])->toBe('the author');
})->expectOutputRegex('/.*/');

test('should generate n resources by param', function () {
    $filename = getcwd().'/database.json';

    $this->questionMock->shouldReceive('confirmation')->andReturn(true);

    $databaseCommand = new ResourceController();

    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        'num=5',
        'fields="title=sentence&author=name'
    ]);

    $databaseCommand->run($input);

    $data = json_decode(file_get_contents($filename), true);
    expect($data['posts'])->toHaveCount(5);
    expect($data['posts'][0]['title'])->toBeString();
    expect($data['posts'][0]['author'])->toBeString();
    expect($data['posts'][1]['title'])->toBeString();
    expect($data['posts'][1]['author'])->toBeString();
})->expectOutputRegex('/.*/');

test('should generate resource in specified database file', function () {
    $filename = 'data/database.json';

    $this->questionMock->shouldReceive('confirmation')->andReturn(true);

    $databaseCommand = new ResourceController();
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        'filename=data/database.json',
        'fields="title=sentence&author=name"',
    ]);

    $databaseCommand->run($input);

    $data = json_decode(file_get_contents(getcwd().'/'.$filename), true);

    expect($data['posts'])->toHaveCount(1);
    expect($data['posts'][0]['title'])->toBeString();
    expect($data['posts'][0]['author'])->toBeString();
})->expectOutputRegex('/.*/');

test('should add into resources if database already has resources', function () {
    $filename = 'database.json';

    file_put_contents(getcwd().'/'.$filename, '{"posts":[{"title": "previous title", "author": "previous author"}]}');

    /** @var \Faker\Generator|Mock */
    $fakerMock = mock(\Faker\Generator::class);

    $fakerMock->shouldReceive('sentence')->andReturn('new title');
    $fakerMock->shouldReceive('name')->andReturn('new author');

    $this->questionMock->shouldReceive('confirmation')->andReturn(true);

    $databaseCommand = new ResourceController($fakerMock);
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        'fields="title=sentence&author=name"',
    ]);

    $databaseCommand->run($input);

    $data = json_decode(file_get_contents(getcwd().'/'.$filename), true);

    expect($data['posts'])->toHaveCount(2);
    expect($data['posts'][0]['title'])->toBe('previous title');
    expect($data['posts'][0]['author'])->toBe('previous author');
    expect($data['posts'][1]['title'])->toBe('new title');
    expect($data['posts'][1]['author'])->toBe('new author');
})->expectOutputRegex('/.*/');

test('should call faker function with params', function () {
    /** @var \Faker\Generator|Mock */
    $fakerMock = mock(\Faker\Generator::class);

    $fakerMock->shouldReceive('numberBetween')->with(100, 200)->andReturn(150);
    $this->questionMock->shouldReceive('confirmation')->andReturn(true);

    $databaseCommand = new ResourceController($fakerMock);
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        'fields=number=numberBetween.100.200',
    ]);

    $databaseCommand->run($input);
})->expectOutputRegex('/.*/');

test('should throw exception if faker function not exists', function () {
    /** @var \Faker\Generator|Mock */
    $fakerMock = mock(\Faker\Generator::class);

    $fakerMock->shouldReceive('numberBetween')->with(100, 200)->andReturn(150);

    $databaseCommand = new ResourceController($fakerMock);
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        'fields=number=missingFunction',
    ]);

    $databaseCommand->run($input);
})
->throws(Exception::class, 'generate field function missingFunction not exists')
->expectOutputRegex('/.*/');

test(
    'should throw exception if resource not present',
    function () {
        $databaseCommand = new ResourceController();
        $databaseCommand->boot($this->commandApp);

        $input = new CommandCall([
            'json-server',
            'generate',
            'resource',
            'fields=title=sentence',
        ]);

        $databaseCommand->run($input);
    }
)->throws(InvalidArgumentException::class, 'resource name is missing')
->expectOutputRegex('/.*/');

test('should receive fields by interact mode', function () {
    $filename = 'database.json';

    /** @var \Faker\Generator|Mock */
    $fakerMock = mock(\Faker\Generator::class);

    $fakerMock->shouldReceive('sentence')->andReturn('the title');
    $fakerMock->shouldReceive('name')->andReturn('the author');

    $this->questionMock->shouldReceive('question')->andReturn('title', 'sentence', 'author', 'name', '', 'yes');
    $this->questionMock->shouldReceive('confirmation')->andReturn('yes');

    $databaseCommand = new ResourceController(faker: $fakerMock);
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        '--it-fields',
    ]);

    $databaseCommand->run($input);

    $data = json_decode(file_get_contents(getcwd().'/'.$filename), true);

    expect($data['posts'])->toHaveCount(1);

    expect($data['posts'][0]['title'])->toBe('the title');
    expect($data['posts'][0]['author'])->toBe('the author');
})->expectOutputRegex('/.*/');

test('should generate resource with id with next value', function () {
    $filename = 'database.json';

    file_put_contents(getcwd().'/'.$filename, '{"posts": [{"id": 1, "title": "the title"}]}');

    $this->questionMock->shouldReceive('confirmation')->andReturn(true);

    $databaseCommand = new ResourceController();
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
        'fields="id=id&title=sentence"',
    ]);

    $databaseCommand->run($input);

    $data = json_decode(file_get_contents(getcwd().'/'.$filename), true);

    expect($data['posts'])->toHaveCount(2);
    expect($data['posts'][1]['id'])->toBe(2);
})->expectOutputRegex('/.*/');

test('should enter interact fields if param field not pass', function () {
    $filename = 'database.json';

    /** @var \Faker\Generator|Mock */
    $fakerMock = mock(\Faker\Generator::class);

    $fakerMock->shouldReceive('sentence')->andReturn('the title');
    $fakerMock->shouldReceive('name')->andReturn('the author');

    $this->questionMock->shouldReceive('question')->andReturn('title', 'sentence', 'author', 'name', '', 'yes');
    $this->questionMock->shouldReceive('confirmation')->andReturn('yes');

    $databaseCommand = new ResourceController(faker: $fakerMock);
    $databaseCommand->boot($this->commandApp);

    $input = new CommandCall([
        'json-server',
        'generate',
        'resource',
        'posts',
    ]);

    $databaseCommand->run($input);

    $data = json_decode(file_get_contents(getcwd().'/'.$filename), true);

    expect($data['posts'])->toHaveCount(1);

    expect($data['posts'][0]['title'])->toBe('the title');
    expect($data['posts'][0]['author'])->toBe('the author');
})->expectOutputRegex('/.*/');
