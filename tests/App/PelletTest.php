<?php
declare(strict_types=1);

namespace Test\App;

use App\Pellet;
use App\Tile;

class PelletTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $pellet = new Pellet(new Tile(1, 2, Tile::TYPE_FLOOR), 1);

        self::assertSame(1, $pellet->x());
        self::assertSame(2, $pellet->y());
        self::assertTrue($pellet->isExists());
    }

    public function testNotWall()
    {
        self::expectException(\InvalidArgumentException::class);

        new Pellet(new Tile(1, 2, Tile::TYPE_WALL), 1);
    }

    public function testIsSuper()
    {
        $pellet = new Pellet(new Tile(1, 2, Tile::TYPE_FLOOR), 10);

        self::assertTrue($pellet->isSuper());
    }

    public function testEaten()
    {
        $pellet = new Pellet(new Tile(1, 2, Tile::TYPE_FLOOR), 1);
        self::assertTrue($pellet->isExists());

        $pellet->eaten();
        self::assertTrue($pellet->isEaten());
    }
}
