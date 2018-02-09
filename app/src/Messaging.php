<?php

namespace OlecaeBackend;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

function getGeometry() {
    return [
        [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 0, 0, 1],
        [1, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 1],
        [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
    ];
}

function getRandomPos($geom, $currentPlayers) {
    $possibilities = [];
    foreach ($geom as $y => $row) {
        foreach ($row as $x => $val) {
            if ($val === 0) {
                $possibilities[] = ['x' => $x, 'y' => $y];
            }
        }
    }
    $idx = rand(0, count($possibilities));
    return $possibilities[$idx];
}

function getPlayerName() {
    static $currentPlayerNum = 0;
    return 'Player ' . ++$currentPlayerNum;
}

class Messaging implements MessageComponentInterface
{
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "Creating websocket chat-server\n";
        flush();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        $newPlayer      = getPlayerName();
        $geom           = getGeometry();
        $currentPlayers = [];

        foreach ($this->clients as $client) {
            $clientData = $this->clients[$client];
            if ($clientData['name']) {
                $currentPlayers[$clientData['name']] = $clientData['pos'];
            }
        }

        $newPlayerPos = getRandomPos($geom, $currentPlayers);

        $newPlayerMsg = [
            'type' => 'welcome',
            'name' => $newPlayer,
            'pos' => $newPlayerPos,
            'geom' => $geom,
            'currentPlayers' => $currentPlayers,
        ];

        $newPlayerData = [
            'name' => $newPlayer,
            'pos' => $newPlayerPos,
        ];

        $oldPlayerMsg         = $newPlayerData;
        $oldPlayerMsg['type'] = 'playerconnect';

        $conn->send(json_encode($newPlayerMsg));

        $this->clients[$conn] = $newPlayerData;

        foreach ($this->clients as $client) {
            if ($client !== $conn) {
                $client->send(json_encode($oldPlayerMsg));
            }
        }
        $this->log(TRUE, $newPlayer, "New connection! ({$conn->resourceId})\n");
        //echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $player     = $this->clients[$from];
        $playerName = $player['name'];
        $decodedMsg = json_decode($msg, TRUE);

        if (!is_array($decodedMsg)) {
            $this->log(FALSE, "$playerName, not array", $decodedMsg);
            return;
        }
        elseif (!array_key_exists('type', $decodedMsg)) {
            $this->log(FALSE, "$playerName, no type", $decodedMsg);
            return;
        }

        switch ($decodedMsg['type']) {
            case 'msg':
                if (array_key_exists('text', $decodedMsg)) {
                    $encodedMsg = json_encode(['type' => 'msg',
                                               'from' => $playerName,
                                               'text' => $decodedMsg['text']]);
                    $this->broadcast($encodedMsg, $from);
                    $this->log(TRUE, $playerName, $decodedMsg);
                }
                else $this->log(FALSE, "$playerName, no text", $decodedMsg);
                break;
            case 'move':
                if (array_key_exists('pos', $decodedMsg)) {
                    $geom = getGeometry();
                    list('x' => $x, 'y' => $y) = $decodedMsg['pos'];
                    list('x' => $oldX, 'y' => $oldY) = $player['pos'];
                    if ($geom[$y][$x] === 0) {
                        $this->broadcast(json_encode([
                                                         'type' => 'move',
                                                         'name' => $playerName,
                                                         'pos' => ['x' => $x, 'y' => $y],
                                                         'oldPos' => ['x' => $oldX, 'y' => $oldY],
                                                     ]));
                        $this->log(TRUE, $playerName, "Move to [$x, $y]\n");

                    }
                    else {
                        $denied = json_encode([
                                                  'type' => 'move',
                                                  'status' => 'denied',
                                              ]);
                        $from->send($denied);
                    }
                }
                else $this->log(FALSE, "$playerName, no pos", $decodedMsg);
                break;
            default:
                $this->log(FALSE, $playerName, $decodedMsg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $playerData = $this->clients[$conn];
        $this->clients->detach($conn);
        echo "Connection {$conn->resourcceId} has disconnected\n";
        $this->broadcast(json_encode([
                                         'type' => 'playerdisconnect',
                                         'name' => $playerData['name'],
                                     ]));
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occured: {$e->getMessage()}\n";
        $this->onClose($conn);
        $conn->close();
    }

    protected function broadcast($msg, $exclude = NULL) {
        foreach ($this->clients as $client) {
            if ($exclude !== $client) {
                $client->send($msg);
            }
        }
    }

    protected function log($status, $playerName, $msg = NULL) {
        echo ($status ? 'Good' : 'Bad') . "stuff from ${playerName}:\n" . ($msg ? print_r($msg, TRUE) : '');
        flush();
    }
}
