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
        self::assertSame([2, 3], $point->ak());
        self::assertSame('2.3', $point->ck());
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

    public function testVerticalDirection()
    {
        $point = new Point(5, 2);

        self::assertSame(Point::TOP, $point->verticalDirection(new Point(5, 1)));
        self::assertSame(Point::BOTTOM, $point->verticalDirection(new Point(5, 3)));
        self::assertSame(Point::EQUAL, $point->verticalDirection(new Point(5, 2)));
    }

    public function testHorizontalDirection()
    {
        $point = new Point(2, 5);

        self::assertSame(Point::LEFT, $point->horizontalDirection(new Point(1, 2)));
        self::assertSame(Point::RIGHT, $point->horizontalDirection(new Point(3, 2)));
        self::assertSame(Point::EQUAL, $point->horizontalDirection(new Point(2, 5)));
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

    public function dataTestNextPoint()
    {
        return [
            [1, 0, Point::TOP],
            [2, 1, Point::RIGHT],
            [1, 2, Point::BOTTOM],
            [0, 1, Point::LEFT],
        ];
    }

    /**
     * @dataProvider dataTestNextPoint
     * @param int $ex
     * @param int $ey
     * @param int $direction
     */
    public function testNextPoint(int $ex, int $ey, int $direction)
    {
        $point = new Point(1, 1);
        self::assertSame((new Point($ex, $ey))->ck(), $point->nextPoint($direction)->ck());
    }
}
