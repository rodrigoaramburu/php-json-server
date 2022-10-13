<?php

declare(strict_types=1);

namespace JsonServer\Utils;

use SplStack;
use Minicli\Output\CLIThemeInterface;
use Minicli\Output\Theme\DefaultTheme;
use Minicli\Output\OutputFilterInterface;
use RuntimeException;

class TagFilter implements OutputFilterInterface
{
    /**
     * theme
     *
     * @var CLIThemeInterface
     */
    protected CLIThemeInterface $theme;

    /**
     * ColorOutputFilter constructor
     *
     * @param CLIThemeInterface|null $theme If a theme is not set, the default CLITheme will be used.
     */
    public function __construct(CLIThemeInterface $theme = null)
    {
        $this->theme = $theme ?? new DefaultTheme();
    }

    /**
     * Gets the CLITheme
     *
     * @return CLIThemeInterface
     */
    public function getTheme(): CLIThemeInterface
    {
        return $this->theme;
    }

    /**
     * Sets the CLITheme
     *
     * @param CLIThemeInterface $theme
     * @return void
     */
    public function setTheme(CLIThemeInterface $theme): void
    {
        $this->theme = $theme;
    }

    public function filter(string $message, ?string $style = null): string
    {
        $stack = new SplStack();
        $offset = 0;
        do {
            preg_match('/<(.*)>/U', $message, $output_array, 0, $offset);
            if (empty($output_array)) {
                break;
            }

            $styleColor = $this->theme->getStyle(str_replace('/', '', $output_array[1]));
            if ($styleColor == $this->theme->getStyle('default')) {
                $offset = strpos($message, $output_array[1], $offset) ;
                continue;
            }

            if (!str_starts_with($output_array[1], '/')) {
                $stack->push($styleColor);
            } else {
                if ($stack->isEmpty()) {
                    throw new RuntimeException("tag <$output_array[1]> closes without open");
                }
                $stack->pop();
                $styleColor = !$stack->isEmpty() ? $stack->top() : $this->theme->getStyle($style);
            }

            $output_array[1] = str_replace('/', '\/', $output_array[1]);

            $message = preg_replace(
                '/<'.$output_array[1].'>/U',
                "\e[0m\e[".implode(';', $styleColor).'m',
                $message,
                1
            );
        } while (!empty($output_array));

        return $message;
    }
}
