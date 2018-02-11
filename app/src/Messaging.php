<?php

namespace OlecaeBackend;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;


function getPlayerName() {
    static $currentPlayerNum = 0;
    return 'Player ' . ++$currentPlayerNum;
}

class Messaging implements MessageComponentInterface
{
    protected $clients;
    protected $gameState;

    public function __construct(GameState $gameState) {
        $this->gameState = $gameState;
        $this->clients   = new \SplObjectStorage;
        echo "Creating websocket chat-server\n";
        flush();
    }

    public function onOpen(ConnectionInterface $conn) {

        $this->clients->attach($conn);

        $newPlayer      = getPlayerName();
        $currentPlayers = $this->gameState->getPlayers();

        $newPlayerData = NULL;
        try {
            $newPlayerData = $this->gameState->addPlayer($newPlayer);
        }
        catch (\Exception $ex) {
            $conn->close();
            $this->log(FALSE, $newPlayer, $ex);
            return;
        }

        $newPlayerMsg = [
            'type' => 'welcome',
            'name' => $newPlayer,
            'data' => $newPlayerData,
            'geom' => $this->gameState->getGeometry(),
            'currentPlayers' => $currentPlayers,
        ];

        $oldPlayerMsg = [
            'type' => 'playerconnect',
            'name' => $newPlayer,
            'data' => $newPlayerData,
        ];

        $conn->send(json_encode($newPlayerMsg));

        $this->clients[$conn] = $newPlayer;
        $this->broadcast($oldPlayerMsg, $conn);

        $this->log(TRUE, $newPlayer, "New connection! ({$conn->resourceId})\n");
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $playerName = $this->clients[$from];
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
                    $msg = ['type' => 'msg',
                            'from' => $playerName,
                            'text' => $decodedMsg['text']];
                    $this->broadcast($msg, $from);
                    $this->log(TRUE, $playerName, $decodedMsg);
                }
                else $this->log(FALSE, "$playerName, no text", $decodedMsg);
                break;
            case 'turn':
                if (array_key_exists('dir', $decodedMsg)) {
                    $dir               = $decodedMsg['dir'];
                    $resultMsg         = $this->gameState->turn($playerName, $dir);
                    $resultMsg['type'] = 'turn';
                    $this->broadcast($resultMsg);
                }
                else $this->log(FALSE, "$playerName, no dir", $decodedMsg);
                break;
            case 'move':
                if (array_key_exists('pos', $decodedMsg)) {
                    $pos       = $decodedMsg['pos'];
                    $resultMsg = $this->gameState->move($playerName, $pos);
                    if (!array_key_exists('text', $resultMsg)) {
                        $resultMsg['type'] = 'move';
                        $this->broadcast($resultMsg);
                    }
                    else {
                        $resultMsg['type'] = 'msg';
                        $from->send(json_encode($resultMsg));
                    }
                }
                else $this->log(FALSE, "$playerName, no pos", $decodedMsg);
                break;
            default:
                $this->log(FALSE, $playerName, $decodedMsg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $playerName = $this->clients[$conn];
        $this->clients->detach($conn);
        echo "Connection {$conn->resourcceId} has disconnected\n";
        $this->broadcast(['type' => 'playerdisconnect', 'name' => $playerName]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occured: {$e->getMessage()}\n";
        $this->onClose($conn);
        $conn->close();
    }

    protected function broadcast($msg, $exclude = NULL) {
        $encodedMsg = json_encode($msg);
        foreach ($this->clients as $client) {
            if ($exclude !== $client) {
                $client->send($encodedMsg);
            }
        }
    }

    protected function log($status, $playerName, $msg = NULL) {
        echo ($status ? 'Good' : 'Bad') . "stuff from ${playerName}:\n" . ($msg ? print_r($msg, TRUE) : '');
        flush();
    }
}
