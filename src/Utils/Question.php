<?php

declare(strict_types=1);

namespace JsonServer\Utils;

use Minicli\App;
use Minicli\ServiceInterface;

class Question implements ServiceInterface
{
    private App $app;
    private $in;
    private string $highlightStyle = 'success';

    public function __construct(string $stream = 'php://stdin')
    {
        $this->in = fopen($stream, 'r+');
    }

    public function load(App $app): void
    {
        $this->app = $app;
    }

    public function getIn()
    {
        return $this->in;
    }

    public function setHighlightStyle(string $highlightStyle): void
    {
        $this->highlightStyle = $highlightStyle;
    }

    public function question(string $message, string $default = ''): string
    {
        $defaultColorized = $this->app->getPrinter()->filterOutput($default, $this->highlightStyle);
        $defaultMessage = $default == '' ? '' : "[$defaultColorized] " ;
        $this->app->getPrinter()->rawOutput($message . ' ' . $defaultMessage);
        $input = rtrim(fgets($this->in), "\n");

        return ! empty($input) ? $input : $default;
    }


    public function confirmation(string $message, bool $default, $trueAnswerRegex = '/^(y|s)/i', array $yesNoMessage = []): bool
    {
        if (!empty($yesNoMessage)) {
            $yes = $default == true ? $this->app->getPrinter()->filterOutput($yesNoMessage[0], $this->highlightStyle) : $yesNoMessage[0];
            $no = $default == false ? $this->app->getPrinter()->filterOutput($yesNoMessage[1], $this->highlightStyle) : $yesNoMessage[1];

            $defaultMessage =  "[$yes/$no] " ;
        }

        $this->app->getPrinter()->rawOutput($message .  ' ' . ($defaultMessage ?? ''));
        $input = rtrim(fgets($this->in), "\n");
        if (empty($input)) {
            return $default;
        }

        return (bool) preg_match($trueAnswerRegex, $input);
    }

    public function choice(string $message, array $options, int $default): string|false
    {
        foreach ($options as $i => $option) {
            $num = $this->app->getPrinter()->filterOutput("[$i]", $this->highlightStyle);
            $this->app->getPrinter()->rawOutput("$num $option\n");
        }


        $defaultOptionColorized = $this->app->getPrinter()->filterOutput(strval($i), $this->highlightStyle);
        $this->app->getPrinter()->rawOutput($message . " [$defaultOptionColorized] ");
        $input = rtrim(fgets($this->in), "\n");
        if ($input === '') {
            return $options[$default];
        }

        if (array_key_exists($input, $options)) {
            return $options[$input];
        } else {
            $this->app->getPrinter()->error('invalid option');
            return false;
        }
    }

    public function multiChoice(string $message, array $options, array $default): array|false
    {
        foreach ($options as $i => $option) {
            $num = $this->app->getPrinter()->filterOutput("[$i]", $this->highlightStyle);
            $this->app->getPrinter()->rawOutput("$num $option\n");
        }

        $defaultOptionColorized = array_map(function ($op) {
            return $this->app->getPrinter()->filterOutput(strval($op), $this->highlightStyle);
        }, $default);
        $defaultOptionColorized = implode(', ', $defaultOptionColorized);

        $this->app->getPrinter()->rawOutput($message . " [$defaultOptionColorized] ");
        $input = rtrim(fgets($this->in), "\n");

        if ($input === '') {
            return array_map(fn ($op) => $options[$op], $default);
        }

        $inputOptions = explode(',', $input);
        $diff = array_diff($inputOptions, array_keys($options));
        if ($diff) {
            $this->app->getPrinter()->error('invalid options: '. implode(',', $diff));
            return false;
        }

        return array_map(fn ($op) => $options[$op], $inputOptions);
    }
}
