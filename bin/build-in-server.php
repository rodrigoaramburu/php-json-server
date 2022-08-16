<?php

use JsonServer\Middlewares\StaticMiddleware;
use JsonServer\Server;

$root_app = dirname(__DIR__);

if (! is_file($root_app.'/vendor/autoload.php')) {
    $root_app = dirname(__DIR__, 4);
}

require $root_app.'/vendor/autoload.php';

$server = new Server([
    'database-file' => $_ENV['DATA_DIR'].'/database.json',
]);

if ($_ENV['USE_STATIC_ROUTE'] == 'true') {
    $staticMiddeware = new StaticMiddleware($dataDir.'/static.json');
    $server->addMiddleware($staticMiddeware);
}

$path = $_SERVER['REQUEST_URI'];
$body = file_get_contents('php://input');
$headers = getallheaders();
$response = $server->handle($_SERVER['REQUEST_METHOD'], $path, $body, $headers);

$server->send($response);
