<?php

use JsonServer\Middlewares\StaticMiddleware;
use JsonServer\Server;

require __DIR__.'/../vendor/autoload.php';

$server = new Server(__DIR__.'/db.json');

$staticMiddeware = new StaticMiddleware(__DIR__.'/static.json');
$server->addMiddleware($staticMiddeware);

$path = $_SERVER['REQUEST_URI'];
$body = file_get_contents('php://input');
$headers = getallheaders();
$response = $server->handle($_SERVER['REQUEST_METHOD'], $path, $body, $headers);

$server->send($response);
