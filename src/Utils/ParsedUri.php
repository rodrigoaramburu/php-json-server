<?php

declare(strict_types=1);

namespace JsonServer\Utils;

use stdClass;

class ParsedUri
{
    private function __construct(
        public readonly ?object $currentResource
    ) {
    }

    public static function parseUri(string $uri): ParsedUri
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        $parts = explode('/', $path);
        $parts = array_values(array_filter($parts, fn ($v) => ! empty($v)));

        $parent = null;
        $currentResource = null;
        for ($i = 0; $i < count($parts); $i += 2) {
            $tmp = $currentResource;
            $currentResource = new stdClass();
            $currentResource->name = $parts[$i];
            $currentResource->id = array_key_exists($i + 1, $parts) ? (int) $parts[$i + 1] : null;
            $currentResource->parent = $tmp;
        }

        return new ParsedUri(
            currentResource: $currentResource
        );
    }
}
