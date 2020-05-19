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

    public function testPac()
    {
        $tick = new Tick(1);
        $pac = new Pac(1, 1, $tick->id(), new Point(0, 0), Pac::TYPE_ROCK, 2, 3);

        $tick->observePac($pac);
        self::assertTrue($tick->isPacVisible(1, 1));
        self::assertSame($pac, $tick->visiblePac(1, 1));
        self::assertInstanceOf(\ArrayObject::class, $tick->visiblePacs());
        self::assertSame($pac, $tick->visiblePacInPoint(new Point(0, 0)));
        self::assertCount(1, $tick->visiblePacs());
    }

    public function testPellet()
    {
        $tick = new Tick(1);
        $pellet = new Pellet(new Tile(0, 0, Tile::TYPE_FLOOR), 1);

        $tick->observePellet($pellet);
        self::assertTrue($tick->isPelletVisible($pellet));
        self::assertSame($pellet, $tick->visiblePellet($pellet));
        self::assertInstanceOf(\ArrayObject::class, $tick->visiblePellets());
        self::assertCount(1, $tick->visiblePellets());
    }
}
