<?php

declare(strict_types=1);

use JsonServer\Utils\ParsedUri;

test('should parse a uri with a entity', function () {
    $parsedUri = ParsedUri::parseUri('/posts');

    expect($parsedUri->currentEntity->name)->toBe('posts');
});

test('should parse a uri with a entity and id', function () {
    $parsedUri = ParsedUri::parseUri('/posts/2');

    expect($parsedUri->currentEntity->name)->toBe('posts');
    expect($parsedUri->currentEntity->id)->toBe(2);
});

test('should parse uri with a inner entity', function () {
    $parsedUri = ParsedUri::parseUri('/posts/2/comments');

    expect($parsedUri->currentEntity->name)->toBe('comments');
    expect($parsedUri->currentEntity->id)->toBeNull();
    expect($parsedUri->currentEntity->parent->name)->toBe('posts');
    expect($parsedUri->currentEntity->parent->id)->toBe(2);
});
