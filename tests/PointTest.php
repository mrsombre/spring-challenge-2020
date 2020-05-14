<?php
declare(strict_types=1);

class PointTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $point = new Point(1, 2);

        self::assertSame(1, $point->x());
        self::assertSame(2, $point->y());
    }

    public function testIsSame()
    {
        $point = new Point(1, 1);

        self::assertTrue($point->isSame(new Point(1, 1)));
        self::assertTrue($point->isSame($point));
        self::assertFalse($point->isSame(new Point(0, 0)));
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
            [Point::LEFT, 0, 1],
            [Point::RIGHT, 2, 1],
            [Point::TOP, 1, 0],
            [Point::BOTTOM, 1, 2],
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
