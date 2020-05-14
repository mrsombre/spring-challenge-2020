<?php
declare(strict_types=1);

class FieldTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $field = new Field([new Tile(0, 0, Tile::TYPE_WALL), new Tile(1, 0, Tile::TYPE_FLOOR)], 2, 1);

        self::assertSame(2, $field->width());
        self::assertSame(1, $field->height());
    }

    public function testParse()
    {
        $raw = [
            '# ',
            '  ',
        ];
        $field = Field::factory($raw);

        self::assertFalse($field->tile(0, 0)->isFloor());
        self::assertTrue($field->tile(1, 1)->isFloor());
    }

    public function dataTestNextTile()
    {
        return [
            [1, 0, Point::TOP],
            [2, 1, Point::RIGHT],
            [1, 2, Point::BOTTOM],
            [0, 1, Point::LEFT],
        ];
    }

    /**
     * @dataProvider dataTestNextTile
     * @param int $ex
     * @param int $ey
     * @param int $direction
     */
    public function testNextTile(int $ex, int $ey, int $direction)
    {
        $field = Field::factory([
            '   ',
            '   ',
            '   ',
        ]);

        $point = $field->tile(1, 1);
        self::assertSame($field->tile($ex, $ey), $field->nextTile($point, $direction));
    }

    public function testNextTileEmpty()
    {
        $field = Field::factory([' ']);

        $point = $field->tile(0, 0);
        self::assertNull($field->nextTile($point, Point::TOP));
        self::assertNull($field->nextTile($point, Point::RIGHT));
        self::assertNull($field->nextTile($point, Point::BOTTOM));
        self::assertNull($field->nextTile($point, Point::LEFT));
    }

    public function testAdjacent()
    {
        $field = Field::factory([
            '   ',
            '   ',
            '   ',
        ]);

        $adjacent = $field->adjacent($field->tile(1, 1));
        self::assertCount(4, $adjacent);
        self::assertTrue($field->tile(1, 0)->isSame($adjacent[Point::TOP]));
        self::assertTrue($field->tile(2, 1)->isSame($adjacent[Point::RIGHT]));
        self::assertTrue($field->tile(1, 2)->isSame($adjacent[Point::BOTTOM]));
        self::assertTrue($field->tile(0, 1)->isSame($adjacent[Point::LEFT]));

        $field = Field::factory([
            '# #',
        ]);
        $adjacent = $field->adjacent($field->tile(1, 0));
        self::assertCount(0, $adjacent);
    }

    public function testVector()
    {
        $field = Field::factory([
            '   ',
            '   ',
            '#  ',
        ]);

        self::assertCount(2, $field->vector(new Point(0, 0), Point::RIGHT));
        self::assertCount(1, $field->vector(new Point(0, 0), Point::BOTTOM));
    }

    public function testPathsCount()
    {
        $field = Field::factory([
            '   ',
            '   ',
            '   ',
        ]);

        self::assertSame(4, $field->pathsCount(new Point(1, 1)));
        self::assertSame(2, $field->pathsCount(new Point(0, 0)));
        self::assertSame(3, $field->pathsCount(new Point(1, 2)));
    }

    public function testPaths()
    {
        $field = Field::factory([
            '   ',
            '   ',
            '   ',
        ]);

        self::assertCount(4, $field->paths(new Point(1, 1)));
        self::assertCount(2, $field->paths(new Point(0, 0)));

        $field = Field::factory([
            ' # ',
            '# #',
            ' # ',
        ]);

        self::assertCount(0, $field->paths(new Point(0, 0)));
        self::assertCount(0, $field->paths(new Point(1, 1)));
    }
}
