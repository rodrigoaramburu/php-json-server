<?php

declare(strict_types=1);

use JsonServer\Database;
use JsonServer\Exceptions\NotFoundEntityException;

test('should return data from entity', function () {
    $database = new Database(__DIR__.'/fixture/db-posts.json');

    $data = $database->from('posts')->get();

    expect($data)->toBeArray();
    expect($data)->toHaveCount(2);
    expect($data[0])->toMatchArray([
        'id' => 1,
        'title' => 'Lorem ipsum dolor sit amet',
        'author' => 'Rodrigo',
        'content' => 'Nunc volutpat ipsum eget sapien ornare...',
    ]);

    expect($data[1])->toMatchArray([
        'id' => 2,
        'title' => 'Duis quis arcu mi',
        'author' => 'Rodrigo',
        'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
    ]);
});

test('should return an entity by id', function () {
    $database = new Database(__DIR__.'/fixture/db-posts.json');

    $data = $database->from('posts')->find(2);

    expect($data)->toBeArray();
    expect($data)->toMatchArray([
        'id' => 2,
        'title' => 'Duis quis arcu mi',
        'author' => 'Rodrigo',
        'content' => 'Suspendisse auctor dolor risus, vel posuere libero...',
    ]);
});

test('should return null if id does not exists', function () {
    $database = new Database(__DIR__.'/fixture/db-posts.json');

    $data = $database->from('posts')->find(3);

    expect($data)->toBeNull();
});

test('should save an entity and add a sequencial id', function () {
    $dbJsonDir = __DIR__.'/fixture/db-posts-save.json';
    file_put_contents($dbJsonDir, '{"posts": []}');

    $database = new Database($dbJsonDir);

    $database->from('posts')->save([
        'title' => 'Title 1',
        'author' => 'Author 1',
        'content' => 'Content 1',
    ]);

    $database->from('posts')->save([
        'title' => 'Title 2',
        'author' => 'Author 2',
        'content' => 'Content 2',
    ]);

    $data = json_decode(file_get_contents($dbJsonDir), true);
    unlink(__DIR__.'/fixture/db-posts-save.json');

    expect($data['posts'][0])->toMatchArray([
        'id' => 1,
        'title' => 'Title 1',
        'author' => 'Author 1',
        'content' => 'Content 1',
    ]);
    expect($data['posts'][1])->toMatchArray([
        'id' => 2,
        'title' => 'Title 2',
        'author' => 'Author 2',
        'content' => 'Content 2',
    ]);
});

test('should throw a exception if entity not exists', function () {
    $database = new Database(__DIR__.'/fixture/db-posts.json');

    $database->from('entityNotFound')->get();
})->throws(NotFoundEntityException::class);
