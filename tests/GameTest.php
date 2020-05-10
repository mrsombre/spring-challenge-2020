<?php
declare(strict_types=1);

class GameTest extends \PHPUnit\Framework\TestCase
{
    public function testSimple()
    {
        $game = new Game;

        self::assertSame(0, $game->tick);
        self::assertSame(0, $game->myScore);
        self::assertSame(0, $game->opponentScore);
    }
}
