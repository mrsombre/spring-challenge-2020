<?php
declare(strict_types=1);

namespace Test\App;

use App\Box;
use App\SpeedOrder;
use Test\GameMaker;

class BoxTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $game = GameMaker::factory([
            '@',
        ]);

        $box = new Box($game);
        self::assertSame(1, $box->freeCount());

        $box->exec();
        self::assertSame(0, $box->freeCount());
        self::assertInstanceOf(SpeedOrder::class, $game->pac(1, 0)->order());
    }
}
