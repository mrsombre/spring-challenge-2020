<?php

namespace Test;

use App\Field;
use App\Game;
use App\Pac;
use App\Point;

class GameMaker
{
    public static function factory(array $map): Game
    {
        $field = Field::factory($map);
        $game = new Game($field);
        $game->turn(0, 0);

        $pacId = 0;
        foreach ($map as $y => $line) {
            for ($x = 0; $x < strlen($line); $x++) {
                if ($line[$x] === '@') {
                    $game->processPac($pacId, 1, $x, $y, Pac::TYPE_ROCK, 0, 0);
                    $pacId++;
                    continue;
                }
                if ($line[$x] === '*') {
                    $game->processPellet($x, $y, 10);
                }
                if ($line[$x] === '.') {
                    $game->processPellet($x, $y, 1);
                }
                if ($line[$x] === ' ') {
                    $game->pellet(new Point($x, $y))->eaten();
                }
            }
        }
        $game->update();

        return $game;
    }
}
