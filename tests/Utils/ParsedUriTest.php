<?php

declare(strict_types=1);

use JsonServer\Utils\ParsedUri;

test('should parse a uri with a resource', function () {
    $parsedUri = ParsedUri::parseUri('/posts');

    expect($parsedUri->currentResource->name)->toBe('posts');
});

test('should parse a uri with a resource and id', function () {
    $parsedUri = ParsedUri::parseUri('/posts/2');

    expect($parsedUri->currentResource->name)->toBe('posts');
    expect($parsedUri->currentResource->id)->toBe(2);
});

test('should parse uri with a inner resource', function () {
    $parsedUri = ParsedUri::parseUri('/posts/2/comments');

    expect($parsedUri->currentResource->name)->toBe('comments');
    expect($parsedUri->currentResource->id)->toBeNull();
    expect($parsedUri->currentResource->parent->name)->toBe('posts');
    expect($parsedUri->currentResource->parent->id)->toBe(2);
});
