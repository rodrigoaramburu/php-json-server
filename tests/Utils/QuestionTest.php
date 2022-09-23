<?php

use JsonServer\Utils\Question;
use Minicli\App;

function getQuestionService()
{
    $app = new App([
        'app_path' => __DIR__.'/../../src/Command/',
    ]);

    $questionService = new Question('php://memory');
    $questionService->load($app);

    return $questionService;
}

function writeStream(array $data, $stream)
{
    foreach ($data as $line) {
        fwrite($stream, $line."\n");
    }
    rewind($stream);
}

it('asserts that question outputs expected text and return', function () {
    $questionService = getQuestionService();
    writeStream(['answer1'], $questionService->getIn());
    $expected = $questionService->question('question minicli');

    expect($expected)->toBe('answer1');
})->expectOutputString('question minicli ');

it('asserts that question outputs expected text with default value return that', function () {
    $questionService = getQuestionService();
    writeStream([''], $questionService->getIn());

    $expected = $questionService->question('question minicli', 'my-default');

    expect($expected)->toBe('my-default');
})->expectOutputString("question minicli [\e[0;32mmy-default\e[0m] ");

it('asserts that confirmation outputs expected text and return true if yes', function () {
    $questionService = getQuestionService();
    writeStream(['yes'], $questionService->getIn());

    $expected = $questionService->confirmation('confirm?', false);

    expect($expected)->toBeTrue();
})->expectOutputString('confirm? ');

it('asserts that confirmation false if no', function () {
    $questionService = getQuestionService();
    writeStream(['no'], $questionService->getIn());

    $expected = $questionService->confirmation('confirm?', false);

    expect($expected)->toBeFalse();
})->expectOutputString('confirm? ');

it('asserts that confirmation return default', function () {
    $questionService = getQuestionService();
    writeStream([''], $questionService->getIn());

    $expected = $questionService->confirmation('confirm?', false);

    expect($expected)->toBeFalse();
})->expectOutputString('confirm? ');

it('asserts that confirmation receive regex for default value', function () {
    $questionService = getQuestionService();
    writeStream(['ok'], $questionService->getIn());

    $expected = $questionService->confirmation('confirm?', false, '/^ok/i');

    expect($expected)->toBeTrue();
})->expectOutputString('confirm? ');

it('asserts that confirmation outputs with default value message', function () {
    $questionService = getQuestionService();
    writeStream(['s'], $questionService->getIn());

    $expected = $questionService->confirmation('confirm?', true, '/^s/i', ['s', 'n']);

    expect($expected)->toBeTrue();
})->expectOutputString("confirm? [\e[0;32ms\e[0m/n] ");

it('asserts that choice output the options and return the option', function () {
    $questionService = getQuestionService();
    writeStream(['1'], $questionService->getIn());

    $expected = $questionService->choice('choose wisely!', ['option 1', 'option 2', 'option 3'], 2);

    expect($expected)->toBe('option 2');
})->expectOutputString(
    <<<"CHOICE"
    \e[0;32m[0]\e[0m option 1
    \e[0;32m[1]\e[0m option 2
    \e[0;32m[2]\e[0m option 3
    choose wisely! [\e[0;32m2\e[0m] 
    CHOICE
);

it('asserts that choice show error if selected invalid option', function () {
    $questionService = getQuestionService();
    writeStream(['42'], $questionService->getIn());

    $expected = $questionService->choice('choose wisely!', ['option 1', 'option 2', 'option 3'], 0);

    expect($expected)->toBeFalse();
})->expectOutputRegex('/invalid option/');

it('asserts that choice return default option if empty input', function () {
    $questionService = getQuestionService();
    writeStream([''], $questionService->getIn());

    $expected = $questionService->choice('choose wisely!', ['option 1', 'option 2', 'option 3'], 0);

    expect($expected)->toBe('option 1');
})->expectOutputRegex('/.*/');

it('asserts that multiChoice output the options and return the option', function () {
    $questionService = getQuestionService();
    writeStream(['2,1'], $questionService->getIn());

    $expected = $questionService->multiChoice('choose wisely!', ['option 1', 'option 2', 'option 3'], [0, 1]);

    expect($expected)->toMatchArray(['option 3', 'option 2']);
})->expectOutputString(
    <<<"CHOICE"
    \e[0;32m[0]\e[0m option 1
    \e[0;32m[1]\e[0m option 2
    \e[0;32m[2]\e[0m option 3
    choose wisely! [\e[0;32m0\e[0m, \e[0;32m1\e[0m] 
    CHOICE
);

it('asserts that multiChoice show error if selected invalid option', function () {
    $questionService = getQuestionService();
    writeStream(['0, 42'], $questionService->getIn());

    $expected = $questionService->multiChoice('choose wisely!', ['option 1', 'option 2', 'option 3'], [0, 1]);

    expect($expected)->toBeFalse();
})->expectOutputRegex('/invalid option/');

it('asserts that choice return default options if empty input', function () {
    $questionService = getQuestionService();
    writeStream([''], $questionService->getIn());

    $expected = $questionService->multiChoice('choose wisely!', ['option 1', 'option 2', 'option 3'], [2, 0]);

    expect($expected)->toMatchArray(['option 3', 'option 1']);
})->expectOutputRegex('/.*/');
