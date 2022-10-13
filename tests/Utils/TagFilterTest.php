<?php

declare(strict_types=1);

use JsonServer\Utils\TagFilter;
use Minicli\Output\OutputHandler;
use Minicli\Output\Filter\ColorOutputFilter;

function getOutputHandler()
{
    $handler = new OutputHandler();
    $handler->registerFilter(new ColorOutputFilter());
    $handler->registerFilter(new TagFilter());

    return $handler;
}

it('asserts that tag is replace with the style', function () {
    $printer = getOutputHandler();
    $printer->out("<success>testing minicli</success>");
})->expectOutputString("\e[1;37m" . s('success') . "testing minicli" . s('default') . "\e[0m");

it('asserts that 2 tags are replace with the respective style', function () {
    $printer = getOutputHandler();
    $printer->out("<success_alt>testing</success_alt> <success>minicli</success>");
})->expectOutputString("\e[1;37m" . s('success_alt') . "testing" . s('default') . " " . s('success') . "minicli" . s('default') . "\e[0m");

it('assert that inner tag open and closes correctly', function () {
    $printer = getOutputHandler();
    $printer->out("<success>testing <error>minicli</error> :)</success>");
})->expectOutputString("\e[1;37m" . s('success'). "testing ". s('error')."minicli".s('success')." :)". s('default') . "\e[0m");

it('assert that if the tag is not a style not replace', function () {
    $printer = getOutputHandler();
    $printer->out("<p>testing minicli</p>");
})->expectOutputString("\e[1;37m" . "<p>testing minicli</p>" . "\e[0m");

it('assert that throws an exception if tag closes without open', function () {
    $printer = getOutputHandler();
    $printer->out("testing</success> minicli");
})->throws('tag </success> closes without open');
