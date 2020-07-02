<?php
declare(strict_types=1);

namespace Test\Strategy;

use Test\GameMaker;
use App\PriorityPathStrategy;
use App\Box;
use App\Pac;
use App\MoveOrder;

class PriorityPathStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testMoveToPellet()
    {
        $game = GameMaker::factory([
            '## ##',
            '#.@ #',
        ]);

        $box = new Box($game, [
            PriorityPathStrategy::class,
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

        $pac = $game->pac(Pac::MINE, 0);

        $box = new Box($game, [
            PriorityPathStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('3.1', $pac->order()->pos()->ck());
    }

    public function testChooseNearest()
    {
        $game = GameMaker::factory([
            '#. @. #',
        ]);

        $box = new Box($game, [
            PriorityPathStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('4.0', $pac->order()->pos()->ck());
    }

    public function testNotEnterDeadEnd()
    {
        $game = GameMaker::factory([
            '#  @  .#',
        ]);

        $pac = $game->pac(Pac::MINE, 0);

        $game->turn();
        $game->processPac(0, Pac::MINE, 3, 0, Pac::TYPE_ROCK, 5, 0);

        $box = new Box($game, [
            PriorityPathStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('5.0', $pac->order()->pos()->ck());
    }

    public function testDontChooseOccupied()
    {
        $game = GameMaker::factory([
            '# @@....#',
        ]);

        $box = new Box($game, [
            PriorityPathStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('1.0', $pac->order()->pos()->ck());
    }

    public function testDontEnterOccupied()
    {
        $game = GameMaker::factory([
            '##@##',
            '##*##',
            '##.##',
            '## .#',
            '# .@#',
        ]);

        $box = new Box($game, [
            PriorityPathStrategy::class,
        ]);
        $box->exec();

        $pac0 = $game->pac(Pac::MINE, 0);
        $pac1 = $game->pac(Pac::MINE, 1);
        self::assertInstanceOf(MoveOrder::class, $pac0->order());
        self::assertSame('2.1', $pac0->order()->pos()->ck());
        self::assertSame('3.3', $pac1->order()->pos()->ck());
    }

    public function testDontEnterOccupiedStrongWithEnemy()
    {
        $game = GameMaker::factory([
            '##@.#',
            '##*##',
            '##.##',
            '##..#',
        ]);

        $pac0 = $game->pac(Pac::MINE, 0);
        $stronger = Pac::RULES[$pac0->type()];

        $game->turn();
        $game->processPac(0, Pac::MINE, 2, 0, $pac0->type(), 0, 0);
        $game->processPac(0, Pac::ENEMY, 2, 3, $stronger, 0, 0);

        $box = new Box($game, [
            PriorityPathStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $pac0->order());
        self::assertSame('3.0', $pac0->order()->pos()->ck());
    }

    public function testEnterDeadEnd()
    {
        $game = GameMaker::factory([
            '##@.#',
            '## ##',
            '## ##',
        ]);

        $pac0 = $game->pac(Pac::MINE, 0);
        $game->turn();
        $game->processPac(0, Pac::MINE, 2, 0, $pac0->type(), 5, 10);
        $game->processPac(0, Pac::ENEMY, 2, 2, Pac::RULES[$pac0->type()], 0, 0);

        $box = new Box($game, [
            PriorityPathStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $pac0->order());
        self::assertSame('3.0', $pac0->order()->pos()->ck());
    }
}
