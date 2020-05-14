<?php
declare(strict_types=1);

namespace Test\App;

use App\Tick;
use App\Point;
use App\Tile;
use App\Pellet;
use App\Pac;

class TickTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $tick = new Tick(1);

        self::assertSame(1, $tick->id());
    }

    public function testVisiblePac()
    {
        $tick = new Tick(1);

        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 2, 3);
        $tick->appendPac($pac);
        self::assertSame($pac, $tick->visiblePac(1, 1));
        self::assertTrue($tick->isPacVisible(1, 1));
        $pac = new Pac(1, 0, 1, new Point(0, 0), Pac::TYPE_ROCK, 2, 3);
        $tick->appendPac($pac);
        self::assertCount(2, $tick->visiblePacs());
        self::assertCount(1, $tick->visiblePacs(Pac::MINE));
    }

    public function testVisiblePellet()
    {
        $tick = new Tick(1);
        $pellet = new Pellet(new Tile(0, 0, Tile::TYPE_FLOOR), 1);

        $tick->appendPellet($pellet);
        self::assertSame($pellet, $tick->visiblePellet($pellet));
        self::assertTrue($tick->isPelletVisible($pellet));
        self::assertCount(1, $tick->visiblePellets());
    }
}
