<?php

declare(strict_types=1);

namespace JsonServer\Utils;

use stdClass;

class ParsedUri
{
    private function __construct(
        private readonly array $entities,
    ) {
    }

    public static function parseUri(string $uri): ParsedUri
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        $parts = explode('/', $path);
        $parts = array_values(array_filter($parts, fn ($v) => ! empty($v)));

        $entities = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            $en = new stdClass;
            $en->name = $parts[$i];
            $en->id = array_key_exists($i + 1, $parts) ? (int) $parts[$i + 1] : null;
            $entities[] = $en;
        }

        return new ParsedUri(
            entities: $entities
        );
    }

    public function entity(int $index): object
    {
        return $this->entities[$index];
    }
}
