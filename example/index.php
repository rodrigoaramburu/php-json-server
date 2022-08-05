<?php

use JsonServer\Server;

require __DIR__.'/../vendor/autoload.php';

$server = new Server(__DIR__.'/db.json');

$path = $_SERVER['REQUEST_URI'];
$data = file_get_contents('php://input');
$response = $server->handle($_SERVER['REQUEST_METHOD'], $path, $data);

$server->send($response);
