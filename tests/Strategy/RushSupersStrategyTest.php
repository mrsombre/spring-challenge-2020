<?php
declare(strict_types=1);

namespace Test\Strategy;

use Test\GameMaker;
use App\RushSupersStrategy;
use App\Box;
use App\Pac;
use App\MoveOrder;

class RushSupersStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testMoveToSuper()
    {
        $game = GameMaker::factory([
            '@.',
            '.*',
        ]);

        $box = new Box($game, [
            RushSupersStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertInstanceOf(MoveOrder::class, $pac->order());
        self::assertSame('1.1', $pac->order()->pos()->ck());
    }

    public function testAssignOnlyHalf()
    {
        $game = GameMaker::factory([
            '@...',
            '@**.',
        ]);

        $box = new Box($game, [
            RushSupersStrategy::class,
        ]);
        $box->exec();

        $pac = $game->pac(Pac::MINE, 0);
        self::assertNull($pac->order());

        self::assertTrue($game->pellet($game->field()->tile(2,1))->isEaten());
    }
}
