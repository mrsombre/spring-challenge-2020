<?php
declare(strict_types=1);

class BoxTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);
        $game->turn(0, 0);
        $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPac(1, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPac(2, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);

        $box = new Box($game);
        self::assertSame(3, $box->countFreePacs());

        $box->exec();
        self::assertSame(0, $box->countFreePacs());
        self::assertInstanceOf(NoopOrder::class, $game->pac(1, 0)->order());
    }
}
