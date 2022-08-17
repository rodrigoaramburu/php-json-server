<?php

declare(strict_types=1);

use JsonServer\Database;
use JsonServer\Exceptions\NotFoundResourceException;
use JsonServer\Exceptions\NotFoundResourceRepositoryException;
use JsonServer\Query;

afterEach(function () {
    $files = [
        __DIR__.'/fixture/db-posts-save.json',
        __DIR__.'/fixture/db-posts-update.json',
        __DIR__.'/fixture/db-posts-delete.json',
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('should throw an exception if database file dont exist', function () {
    $database = new Database(__DIR__.'/fixture/db-posts-missing.json');
})->throws(RuntimeException::class, 'cannot open file '.__DIR__.'/fixture/db-posts-missing.json');

test('should return data from resource', function () {
    $database = new Database(__DIR__.'/fixture/db-posts.json');

    $data = $database->from('posts')->query()->get();

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

test('should return an resource by id', function () {
    $database = new Database(__DIR__.'/fixture/db-posts.json');

    $data = $database->from('posts')->query()->find(2);

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

    $data = $database->from('posts')->query()->find(3);

    expect($data)->toBeNull();
});

test('should save an resource and add a sequencial id', function () {
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

test('should throw a exception if resource not exists', function () {
    $database = new Database(__DIR__.'/fixture/db-posts.json');

    $database->from('resourceNotFound')->query()->get();
})->throws(NotFoundResourceRepositoryException::class);

test('should update an resource', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-update.json';

    file_put_contents($dbFileJson, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $database = new Database($dbFileJson);

    $database->from('posts')->update(2, [
        'id' => 2,
        'title' => 'Title Test changed',
        'author' => 'Author Test changed',
        'content' => 'Content Test changed',
    ]);

    $data = json_decode(file_get_contents($dbFileJson), true);

    expect($data['posts'][0])->toMatchArray([
        'id' => 1,
        'title' => 'Lorem ipsum dolor sit amet',
        'author' => 'Rodrigo',
        'content' => 'Nunc volutpat ipsum eget sapien ornare...',
    ]);
    expect($data['posts'][1])->toMatchArray([
        'id' => 2,
        'title' => 'Title Test changed',
        'author' => 'Author Test changed',
        'content' => 'Content Test changed',
    ]);
});

test('should delete an resource', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-delete.json';

    file_put_contents($dbFileJson, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $database = new Database($dbFileJson);

    $database->from('posts')->delete(2);

    $data = json_decode(file_get_contents($dbFileJson), true);

    expect($data['posts'])->toHaveCount(1);

    expect($data['posts'][0])->toMatchArray([
        'id' => 1,
    ]);
});

test('should throw an exception if id not exists', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-delete.json';

    file_put_contents($dbFileJson, file_get_contents(__DIR__.'/fixture/db-posts.json'));

    $database = new Database($dbFileJson);

    $database->from('posts')->delete(3);
})->throws(NotFoundResourceException::class);

test('should filter an resource by its parents', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts.json';

    $database = new Database($dbFileJson);

    $comments = $database
                    ->from('comments')
                    ->query()
                        ->whereParent('posts', 1
                        )->get();

    expect($comments)->toHaveCount(2);
});

test('should filter a resource by field', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts.json';

    $database = new Database($dbFileJson);

    $data = $database->from('posts')->query()->where('title', 'Duis')->get();

    expect($data)->toHaveCount(1);

    expect($data[0]['title'])->toBe('Duis quis arcu mi');
    expect($data[0]['author'])->toBe('Rodrigo');
    expect($data[0]['content'])->toBe('Suspendisse auctor dolor risus, vel posuere libero...');
});

test('should filter a resource by field case insensitive', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts.json';

    $database = new Database($dbFileJson);

    $data = $database->from('posts')->query()->where('title', 'duis')->get();

    expect($data)->toHaveCount(1);

    expect($data[0]['title'])->toBe('Duis quis arcu mi');
    expect($data[0]['author'])->toBe('Rodrigo');
    expect($data[0]['content'])->toBe('Suspendisse auctor dolor risus, vel posuere libero...');
});

test('should order by a field', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-shuffled.json';

    $database = new Database($dbFileJson);

    $data = $database->from('posts')->query()->orderBy('title')->get();

    expect($data[0]['title'])->toBe('Title 1');
    expect($data[1]['title'])->toBe('Title 2');
    expect($data[2]['title'])->toBe('Title 3');
    expect($data[3]['title'])->toBe('Title 4');
});

test('should order by a field in desc order', function () {
    $dbFileJson = __DIR__.'/fixture/db-posts-shuffled.json';

    $database = new Database($dbFileJson);

    $data = $database->from('posts')->query()->orderBy('title', Query::ORDER_DESC)->get();

    expect($data[0]['title'])->toBe('Title 4');
    expect($data[1]['title'])->toBe('Title 3');
    expect($data[2]['title'])->toBe('Title 2');
    expect($data[3]['title'])->toBe('Title 1');
});
