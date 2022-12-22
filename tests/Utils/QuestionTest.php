<?php

use Minicli\App;
use Mockery\Mock;
use Minicli\Input;
use JsonServer\Utils\Question;
use JsonServer\Utils\TagFilter;

beforeEach(function () {
    $app = new App([
        'app_path' => __DIR__.'/../../src/Command/',
    ]);

    $app->getPrinter()->registerFilter(new TagFilter());

    $this->questionService = new Question();
    /** @var Mock|Input */
    $this->inputMock = Mockery::mock(Input::class);
    $this->questionService->changeInput($this->inputMock);
    $this->questionService->load($app);
});

it('asserts that question outputs expected text and return', function () {
    $this->inputMock->shouldReceive('read')->andReturn('answer1');

    $expected = $this->questionService->question('question minicli');

    expect($expected)->toBe('answer1');
})->expectOutputString("\e[1;37m" . "question minicli " . "\e[0m" . PHP_EOL);

it('asserts that question outputs expected text with default value return that', function () {
    $this->inputMock->shouldReceive('read')->andReturn('');

    $expected = $this->questionService->question('question minicli', 'my-default');

    expect($expected)->toBe('my-default');
})->expectOutputString("\e[1;37m" . "question minicli [".s('success')."my-default".s('default')."] \e[0m" . PHP_EOL);

it('asserts that confirmation outputs expected text and return true if yes', function () {
    $this->inputMock->shouldReceive('read')->andReturn('yes');

    $expected = $this->questionService->confirmation('confirm?', false);

    expect($expected)->toBeTrue();
})->expectOutputString("\e[1;37m" . "confirm? ". "\e[0m" . PHP_EOL);

it('asserts that confirmation false if no', function () {
    $this->inputMock->shouldReceive('read')->andReturn('no');

    $expected = $this->questionService->confirmation('confirm?', false);

    expect($expected)->toBeFalse();
})->expectOutputString("\e[1;37m" . "confirm? ". "\e[0m" . PHP_EOL);

it('asserts that confirmation return default', function () {
    $this->inputMock->shouldReceive('read')->andReturn('');

    $expected = $this->questionService->confirmation('confirm?', false);

    expect($expected)->toBeFalse();
})->expectOutputString("\e[1;37m" . "confirm? ". "\e[0m". PHP_EOL);

it('asserts that confirmation receive regex for default value', function () {
    $this->inputMock->shouldReceive('read')->andReturn('ok');

    $expected = $this->questionService->confirmation('confirm?', false, '/^ok/i');

    expect($expected)->toBeTrue();
})->expectOutputString("\e[1;37m" . "confirm? ". "\e[0m" . PHP_EOL);

it('asserts that confirmation outputs with default value message', function () {
    $this->inputMock->shouldReceive('read')->andReturn('s');

    $expected = $this->questionService->confirmation('confirm?', true, '/^s/i', ['s', 'n']);

    expect($expected)->toBeTrue();
})->expectOutputString("\e[1;37m" . "confirm? [". s('success')."s" .s('default') . "/n] " ."\e[0m" . PHP_EOL);

it('asserts that choice output the options and return the option', function () {
    $this->inputMock->shouldReceive('read')->andReturn('1');

    $expected = $this->questionService->choice('choose wisely!', ['option 1', 'option 2', 'option 3'], 2);

    expect($expected)->toBe('option 2');
})->expectOutputString(
    "\e[1;37m" . s('success') . "[0]" . s('default') . " option 1" . "\e[0m" . PHP_EOL .
    "\e[1;37m" . s('success') . "[1]" . s('defautl') . " option 2" . "\e[0m" . PHP_EOL .
    "\e[1;37m" . s('success') . "[2]" . s('default') . " option 3" . "\e[0m" . PHP_EOL .
    "\e[1;37m" . "choose wisely! " . s('success'). "[2]" . s('default') . " \e[0m" . PHP_EOL
);

it('asserts that choice show error if selected invalid option', function () {
    $this->inputMock->shouldReceive('read')->andReturn('42');

    $expected = $this->questionService->choice('choose wisely!', ['option 1', 'option 2', 'option 3'], 0);

    expect($expected)->toBeFalse();
})->expectOutputRegex('/invalid option/');

it('asserts that choice return default option if empty input', function () {
    $this->inputMock->shouldReceive('read')->andReturn('');

    $expected = $this->questionService->choice('choose wisely!', ['option 1', 'option 2', 'option 3'], 0);

    expect($expected)->toBe('option 1');
})->expectOutputRegex('/.*/');

it('asserts that multiChoice output the options and return the option', function () {
    $this->inputMock->shouldReceive('read')->andReturn('2,1');

    $expected = $this->questionService->multiChoice('choose wisely!', ['option 1', 'option 2', 'option 3'], [0, 1]);

    expect($expected)->toMatchArray(['option 3', 'option 2']);
})->expectOutputString(
    "\e[1;37m" . s('success') . "[0]" . s('default') . " option 1" . "\e[0m" . PHP_EOL .
    "\e[1;37m" . s('success') . "[1]" . s('defautl') . " option 2" . "\e[0m" . PHP_EOL .
    "\e[1;37m" . s('success') . "[2]" . s('default') . " option 3" . "\e[0m" . PHP_EOL .
    "\e[1;37m" . "choose wisely! [" . s('success'). "0" . s('default') . ', ' . s('success'). "1" . s('default') .  "] \e[0m" . PHP_EOL
);

it('asserts that multiChoice show error if selected invalid option', function () {
    $this->inputMock->shouldReceive('read')->andReturn('0, 42');

    $expected = $this->questionService->multiChoice('choose wisely!', ['option 1', 'option 2', 'option 3'], [0, 1]);

    expect($expected)->toBeFalse();
})->expectOutputRegex('/invalid option/');

it('asserts that choice return default options if empty input', function () {
    $this->inputMock->shouldReceive('read')->andReturn('');

    $expected = $this->questionService->multiChoice('choose wisely!', ['option 1', 'option 2', 'option 3'], [2, 0]);

    expect($expected)->toMatchArray(['option 3', 'option 1']);
})->expectOutputRegex('/.*/');


test('should that change the style of highlight', function () {
    $this->inputMock->shouldReceive('read')->andReturn('answer1');
    $this->questionService->setHighlightStyle('error');
    $expected = $this->questionService->question('question minicli', 'teste');
    $this->questionService->setHighlightStyle('success');

    expect($expected)->toBe('answer1');
})->expectOutputString("\e[1;37m" . "question minicli [".s('error').'teste'.s('default')."] " . "\e[0m" . PHP_EOL);
