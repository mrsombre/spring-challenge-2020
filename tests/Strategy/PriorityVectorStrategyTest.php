<?php
declare(strict_types=1);

namespace Test\Strategy;

use App\PriorityVectorStrategy;
use App\Field;
use App\Game;
use App\Box;
use App\Pac;
use App\MoveOrder;

class PriorityVectorStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testMove()
    {
        $field = Field::factory([
            '   ',
            ' ##',
            ' ##',
        ]);
        $game = new Game($field);
        $game->turn(0, 0);

        $game->processPellet(1, 0, 1);
        $game->processPellet(2, 0, 1);
        $game->processPellet(0, 1, 1);
        $game->processPellet(0, 2, 1);
        $game->update();

        $game->turn(0, 0);
        $pac = $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPellet(1, 0, 1);
        $game->processPellet(0, 1, 1);
        $game->processPellet(0, 2, 1);
        $game->update();

        $box = new Box($game, [
            PriorityVectorStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('MOVE {id} 0 2', $pac->order->command());
    }

    public function testCross()
    {
        $field = Field::factory([
            '   ',
            '# #',
        ]);
        $game = new Game($field);
        $game->turn(0, 0);

        $pac = $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPellet(1, 0, 1);
        $game->update();

        $box = new Box($game, [
            PriorityVectorStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('MOVE {id} 1 0', $pac->order->command());
    }
}
