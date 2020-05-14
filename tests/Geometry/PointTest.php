<?php
declare(strict_types=1);

namespace Test\Geometry;

use App\Point;
use App\Pellet;
use App\Tile;

class PointTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $point = new Point(2, 3);

        self::assertSame(2, $point->x());
        self::assertSame(3, $point->y());
    }

    public function testIsSame()
    {
        $point = new Point(2, 3);

        self::assertTrue($point->isSame(new Point(2, 3)));
        self::assertTrue($point->isSame($point));
        self::assertFalse($point->isSame(new Point(0, 0)));

        $pellet = new Pellet(new Tile(2, 3, Tile::TYPE_FLOOR), 1);
        self::assertTrue($point->isSame($pellet));
    }

    public function dataTestDistance()
    {
        return [
            [4, 0, 0],
            [4, 4, 4],
            [1, 3, 2],
            [1, 2, 3],
            [2, 0, 2],
            [3, 2, 5],
            [4, -2, 2],
        ];
    }

    /**
     * @dataProvider dataTestDistance
     *
     * @param int $expected
     * @param int $x
     * @param int $y
     */
    public function testDistance(int $expected, int $x, int $y)
    {
        $point = new Point(2, 2);

        self::assertSame($expected, $point->distance(new Point($x, $y)));
    }

    public function dataTestDirection()
    {
        return [
            [Point::TOP, 1, 0],
            [Point::RIGHT, 2, 1],
            [Point::BOTTOM, 1, 2],
            [Point::LEFT, 0, 1],
            [Point::DIAGONAL, 0, 0],
        ];
    }

    /**
     * @dataProvider dataTestDirection
     *
     * @param int $expected
     * @param int $x
     * @param int $y
     */
    public function testDirection(int $expected, int $x, int $y)
    {
        $point = new Point(1, 1);

        self::assertSame($expected, $point->direction(new Point($x, $y)));
    }
}
