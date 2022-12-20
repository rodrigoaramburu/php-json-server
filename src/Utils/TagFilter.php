<?php

declare(strict_types=1);

namespace JsonServer\Utils;
use Minicli\Output\CLIThemeInterface;
use Minicli\Output\Theme\DefaultTheme;
use Minicli\Output\OutputFilterInterface;
use SplStack;

class TagFilter implements OutputFilterInterface
{
    /**
     * theme
     *
     * @var CLIThemeInterface
     */
    protected CLIThemeInterface $theme;

    private SplStack $stackTag;

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
        preg_match_all('#<((\w[^<>]*+)|/(\w[^<>]*+)?)>#ix', $message, $matches, \PREG_OFFSET_CAPTURE);
        $out = "";
        $offset = 0;
        $this->stackTag = new SplStack();
        foreach($matches[0] as $i => $match){
            $tag = $match[0];
            $pos = intval($match[1]);
            $lenght = $pos-$offset;            
            $textPart = mb_substr($message, $offset, $lenght);
            
            $styleColorCode = $this->getStyleColor($tag);
            $out .= $textPart . ($styleColorCode ?? $tag); 

            $offset = $pos + mb_strlen($tag);
        }
        return $out . mb_substr($message, $offset);
    }

    private function getStyleColor($tag): ?string
    {
        $tag = trim($tag, '<>');
        
        if( !$this->existsStyle($tag) ){
            return null;
        }
        
        if ($this->isOpenTag($tag)) {
            $styleColor = $this->theme->getStyle($tag);
            $this->stackTag->push($styleColor);
            return $this->styleToCode($styleColor);
        }

        if ($this->stackTag->isEmpty()) {
            throw new \RuntimeException("tag <$tag> closes without open");
        }
        
        $this->stackTag->pop();
        $styleColor = !$this->stackTag->isEmpty() 
            ? $this->stackTag->top() 
            : $this->theme->getStyle('default');
    

        return $this->styleToCode($styleColor);
    }

    private function styleToCode(array $styleColor): string
    {
        return "\e[0m\e[".implode(';', $styleColor).'m';
    }

    private function isOpenTag($tag): bool
    {
        return !str_starts_with($tag, '/');
    }

    private function existsStyle(string $tag): bool
    {
        $styles = array_keys($this->theme->styles);
        return in_array(str_replace('/', '', $tag), $styles);
    }
}