<?php
declare(strict_types=1);

namespace Test\Strategy;

use Test\GameMaker;
use App\PriorityVectorStrategy;
use App\Box;
use App\Pac;
use App\MoveOrder;

class PriorityVectorStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testMoveToPellet()
    {
        $game = GameMaker::factory([
            '## ##',
            '#.@ #',
        ]);

        $box = new Box($game, [
            PriorityVectorStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('1.1', $pac->order()->pos()->ck());
    }

    public function testChooseLong()
    {
        $game = GameMaker::factory([
            '##.# ##',
            '#.@...#',
        ]);

        $box = new Box($game, [
            PriorityVectorStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('5.1', $pac->order()->pos()->ck());
    }

    public function testChooseNearest()
    {
        $game = GameMaker::factory([
            '#. @. #',
        ]);

        $box = new Box($game, [
            PriorityVectorStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('4.0', $pac->order()->pos()->ck());
    }
}
