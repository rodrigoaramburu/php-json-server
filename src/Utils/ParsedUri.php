<?php

declare(strict_types=1);

namespace JsonServer\Utils;

use stdClass;

class ParsedUri
{
    private function __construct(
        public readonly ?object $currentEntity
    ) {
    }

    public static function parseUri(string $uri): ParsedUri
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        $parts = explode('/', $path);
        $parts = array_values(array_filter($parts, fn ($v) => ! empty($v)));

        $parent = null;
        $currentEntity = null;
        for ($i = 0; $i < count($parts); $i += 2) {
            $tmp = $currentEntity;
            $currentEntity = new stdClass;
            $currentEntity->name = $parts[$i];
            $currentEntity->id = array_key_exists($i + 1, $parts) ? (int) $parts[$i + 1] : null;
            $currentEntity->parent = $tmp;
        }

        return new ParsedUri(
            currentEntity: $currentEntity
        );
    }
}
