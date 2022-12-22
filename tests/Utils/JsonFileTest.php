<?php declare(strict_types=1);

use JsonServer\Utils\JsonFile;


afterEach(function(){
    chmod(__DIR__.'/../fixture/invalid.json',0666);
});

test('should read a file and convert', function(){
    $jsonFile = new JsonFile();
    
    $data = $jsonFile->loadFile(__DIR__.'/../fixture/db-posts.json');

    expect($data['posts'][0]['id'])->toBe(1);
    expect($data['posts'][0]['title'])->toBe('Lorem ipsum dolor sit amet');
    expect($data['posts'][0]['author'])->toBe('Rodrigo');
});

test('should throw exception if file not exists', function(){
    $jsonFile = new JsonFile('r+b');
    
    $data = $jsonFile->loadFile(__DIR__.'/../fixture/missing.json');
})->throws(RuntimeException::class, 'cannot open file '. __DIR__.'/../fixture/missing.json');

test('should throw exception if json format invalid', function(){
    $jsonFile = new JsonFile('r+b');
    
    $data = $jsonFile->loadFile(__DIR__.'/../fixture/invalid.json');
    
})->throws(InvalidArgumentException::class, 'data is not a JSON string');


test('should throw exception if cannot read file', function(){
    $jsonFile = new JsonFile('r+b');
    chmod(__DIR__.'/../fixture/invalid.json',0222);
    $data = $jsonFile->loadFile(__DIR__.'/../fixture/invalid.json');
    
})->throws(RuntimeException::class, 'cannot open file '. __DIR__.'/../fixture/invalid.json');


test('should write data to file', function(){
    $jsonFile = new JsonFile();

    $jsonFile->loadFile(__DIR__.'/../fixture/write.json');

    $jsonFile->writeFile([
        'message' => 'Success'
    ]);

    $expectData = file_get_contents(__DIR__.'/../fixture/write.json');
    expect($expectData)->toBe("{\n    \"message\": \"Success\"\n}");
    unlink(__DIR__.'/../fixture/write.json');
});