<?php

echo "Starting php-server\n";

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\Websocket\WsServer;


use OlecaeBackend\Messaging;
use OlecaeBackend\GameState;

require \dirname(__DIR__) . '/vendor/autoload.php';

$gameState = new GameState();

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Messaging($gameState)
        )
    ),
    2345
);

$server->run();
