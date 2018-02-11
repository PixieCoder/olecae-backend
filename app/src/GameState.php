<?php

namespace OlecaeBackend;

class GameState
{
    protected $geometry;
    protected $players;

    public function __construct() {
        $this->players  = [];
        $this->geometry = [
            [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
            [1, 1, 1, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 1],
            [1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1],
            [1, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1],
            [1, 0, 0, 0, 0, 0, 0, 1, 0, 0, 1, 1, 1, 0, 0, 1],
            [1, 0, 0, 0, 0, 0, 0, 1, 0, 1, 1, 0, 0, 0, 0, 1],
            [1, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1],
            [1, 1, 0, 0, 0, 0, 1, 1, 0, 1, 1, 0, 0, 0, 0, 1],
            [1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 1, 1, 0, 0, 0, 1],
            [1, 0, 0, 0, 1, 1, 0, 1, 0, 0, 0, 1, 1, 0, 0, 1],
            [1, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0, 0, 1],
            [1, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 1, 0, 1],
            [1, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 0, 1, 1, 1],
            [1, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1],
            [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
        ];
    }

    protected function getRandomPos(): array {
        $possibilities = [];
        foreach ($this->geometry as $y => $row) {
            foreach ($row as $x => $val) {
                $pos = ['x' => $x, 'y' => $y];
                if ($this->passable($pos)) {
                    $possibilities[] = $pos;
                }
            }
        }
        $idx = rand(0, count($possibilities) - 1);
        return $possibilities[$idx];
    }

    public function addPlayer(string $name): array {
        if (array_key_exists($name, $this->players)) {
            throw new \Exception("Player already exists!");
        }
        $pos    = $this->getRandomPos();
        $bundle = ['pos' => $pos,
                   'color' => ['r' => rand(25, 255), 'g' => rand(25, 255), 'b' => rand(25, 255)],
                   'dir' => rand(0, 3)];
        return $this->players[$name] = $bundle;
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function getPlayer(string $name): array {
        return $this->players[$name];
    }

    public function getGeometry(): array {
        return $this->geometry;
    }

    public function at(array $pos): int {
        list('x' => $x, 'y' => $y) = $pos;
        if (!is_int($x) || !is_int($y)) {
            throw new \Exception("Bad position parameter sent!");
        }
        return $this->geometry[$y][$x];
    }

    public function passable(array $pos): bool {
        return $this->at($pos) > 0;
    }

    public function move(string $name, array $pos): array {
        if (!$this->passable($pos)) {
            return ['text' => 'denied'];
        }
        $dir = $this->players[$name]['dir'];
        // TODO: Implement more checks here
        $this->players[$name]['pos'] = $pos;
        return ['name' => $name, 'data' => ['pos' => $pos, 'dir' => $dir]];
    }

    public function turn(string $name, int $dir): array {
        // TODO: Cap the dir
        $this->players[$name]['dir'] = $dir;
        return ['name' => $name, 'data' => ['dir' => $dir]];
    }
}
