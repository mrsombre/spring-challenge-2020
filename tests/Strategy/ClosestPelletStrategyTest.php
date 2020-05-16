<?php
declare(strict_types=1);

namespace Test\Strategy;

use Test\GameMaker;
use App\ClosestPelletStrategy;
use App\Box;
use App\Pac;
use App\MoveOrder;

class ClosestPelletStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testMove()
    {
        $game = GameMaker::factory([
            '#@#.#',
            '#   #',
        ]);

        $box = new Box($game, [
            ClosestPelletStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('3.0', $pac->order()->pos()->ck());
    }

    public function testTwoPoints()
    {
        $game = GameMaker::factory([
            '#  @ #',
            '# . .#',
        ]);

        $box = new Box($game, [
            ClosestPelletStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('4.1', $pac->order()->pos()->ck());
    }
}
