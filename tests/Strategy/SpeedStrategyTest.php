<?php
declare(strict_types=1);

namespace Test\Strategy;

use App\NoopOrder;
use Test\GameMaker;
use App\SpeedStrategy;
use App\Box;
use App\Pac;
use App\SpeedOrder;

class SpeedStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testMove()
    {
        $game = GameMaker::factory([
            '# @ #',
        ]);

        $box = new Box($game, [
            SpeedStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(SpeedOrder::class, $pac->order());
    }

    public function testSkipIfCooldown()
    {
        $game = GameMaker::factory([
            '#   #',
        ]);

        $game->processPac(0, Pac::MINE, 2, 0, Pac::TYPE_ROCK, 5, 10);

        $box = new Box($game, [
            SpeedStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertNull($pac->order());
    }

    public function testSkipIfStrongEnemy()
    {
        $game = GameMaker::factory([
            '#@   #',
        ]);

        $mine = $game->pac(Pac::MINE, 0);
        $game->processPac(0, Pac::ENEMY, 4, 0, Pac::stronger($mine->type()), 0, 0);

        $box = new Box($game, [
            SpeedStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertNull($pac->order());
    }
}
