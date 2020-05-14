<?php
declare(strict_types=1);

namespace Test\Strategy;

use App\RushSupersStrategy;
use App\Field;
use App\Game;
use App\Box;
use App\Pac;
use App\MoveOrder;

class RushSupersStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testMoveToSuper()
    {
        $field = Field::factory([
            '     ',
            '     ',
        ]);
        $game = new Game($field);
        $game->turn(0, 0);

        $pac0 = $game->processPac(0, 1, 1, 0, Pac::TYPE_ROCK, 0, 0);
        $pac1 = $game->processPac(1, 1, 2, 0, Pac::TYPE_ROCK, 0, 0);
        $pac2 = $game->processPac(2, 1, 4, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPellet(4, 1, 10);
        $game->processPellet(0, 1, 10);
        $game->update();

        $box = new Box($game, [
            RushSupersStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $pac0->order());
        self::assertSame('MOVE {id} 0 1', $pac0->order->command());
        self::assertInstanceOf(MoveOrder::class, $pac2->order());
        self::assertSame('MOVE {id} 4 1', $pac2->order->command());
        self::assertNull($pac1->order());
    }
}
