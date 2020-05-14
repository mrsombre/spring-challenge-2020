<?php
declare(strict_types=1);

class CloseEnemyStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testWaitIfStronger()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);
        $game->turn(0, 0);

        $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPac(0, 0, 1, 0, Pac::TYPE_SCISSORS, 0, 0);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(NoopOrder::class, $game->pac(1, 0)->order());
    }

    public function testSwitchIfWeaker()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);
        $game->turn(0, 0);

        $game->processPac(0, 1, 0, 0, Pac::TYPE_SCISSORS, 0, 0);
        $game->processPac(0, 0, 1, 0, Pac::TYPE_ROCK, 0, 0);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(SwithOrder::class, $game->pac(1, 0)->order());
        self::assertSame('SWITCH {id} PAPER', $game->pac(1, 0)->order()->command());
    }
}
