<?php

echo "Starting php-fpm\n";
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Websocket\WsServer;

use OlecaeBackend\Chat;

require \dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    2345
);

$server->run();
