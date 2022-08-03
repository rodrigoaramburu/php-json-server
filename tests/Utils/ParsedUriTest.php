<?php

declare(strict_types=1);

use JsonServer\Utils\ParsedUri;

test('should parse a uri with a entity', function () {
    $parsedUri = ParsedUri::parseUri('/posts');

    expect($parsedUri->entity(0)->name)->toBe('posts');
});

test('should parse a uri with a entity and id', function () {
    $parsedUri = ParsedUri::parseUri('/posts/2');

    expect($parsedUri->entity(0)->name)->toBe('posts');
    expect($parsedUri->entity(0)->id)->toBe(2);
});
