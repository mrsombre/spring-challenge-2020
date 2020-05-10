<?php
declare(strict_types=1);

class PointTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $point = new Point(1, 2);

        self::assertSame(1, $point->x);
        self::assertSame(2, $point->y);
    }

    public function dataTestDistance()
    {
        return [
            [4, 0, 0],
            [4, 4, 4],
            [1, 3, 2],
            [1, 2, 3],
            [4, -2, 2],
        ];
    }

    /**
     * @dataProvider dataTestDistance
     */
    public function testDistance(int $expected, int $x, int $y)
    {
        $point = new Point(2, 2);

        self::assertSame($expected, $point->distance(new Point($x, $y)));
    }
}
