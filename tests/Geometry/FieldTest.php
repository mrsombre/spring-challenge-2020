<?php
declare(strict_types=1);

namespace Test\Geometry;

use App\Field;
use App\Point;
use ArrayObject;

class FieldTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $field = new Field([
            ['#', '#', '#'],
            ['#', '#', '#'],
        ]);

        self::assertSame(2, $field->width());
        self::assertSame(3, $field->height());
        self::assertCount(6, $field->tiles());
        self::assertInstanceOf(ArrayObject::class, $field->tiles());
    }

    public function testParse()
    {
        $raw = [
            '# ',
            '  ',
        ];
        $field = Field::factory($raw);

        self::assertTrue($field->tile(0, 0)->isWall());
        self::assertTrue($field->tile(1, 1)->isFloor());
    }

    public function testPortal()
    {
        $raw = [
            ' ',
        ];
        $field = Field::factory($raw);

        self::assertTrue($field->isPortal());
        self::assertArrayHasKey('0.0', $field->portals());
        self::assertInstanceOf(ArrayObject::class, $field->portals());

        $raw = [
            '# #',
        ];
        $field = Field::factory($raw);
        self::assertFalse($field->isPortal());
        self::assertEmpty($field->portals());
    }

    public function testTileMirror()
    {
        $raw = [
            '   ',
        ];
        $field = Field::factory($raw);

        self::assertSame($field->tile(2, 0), $field->ensureTile(-1, 0));
        self::assertSame($field->tile(0, 0), $field->ensureTile(3, 0));
    }

    public function dataTestNextTile()
    {
        return [
            // basic
            [1, 1, Point::TOP, 1, 0],
            [1, 1, Point::RIGHT, 2, 1],
            [1, 1, Point::BOTTOM, 1, 2],
            [1, 1, Point::LEFT, 0, 1],
            // adv
            [0, 0, Point::LEFT, 2, 0],
            [2, 0, Point::RIGHT, 0, 0],
        ];
    }

    /**
     * @dataProvider dataTestNextTile
     *
     * @param int $x
     * @param int $y
     * @param int $direction
     * @param int $ex
     * @param int $ey
     */
    public function testNextTile(int $x, int $y, int $direction, int $ex, int $ey)
    {
        $field = Field::factory([
            '   ',
            '   ',
            '   ',
        ]);

        self::assertSame($field->tile($ex, $ey), $field->nextTile($field->tile($x, $y), $direction));
    }

    public function testNextTileEmpty()
    {
        $field = Field::factory(['# #']);

        self::assertNull($field->nextTile($field->tile(1, 0), Point::TOP));
        self::assertNull($field->nextTile($field->tile(1, 0), Point::BOTTOM));
        self::assertNull($field->nextTile($field->tile(0, 0), Point::LEFT));
        self::assertNull($field->nextTile($field->tile(2, 0), Point::RIGHT));
    }

    public function dataTestAdjacent()
    {
        return [
            // basic
            [1, 1, 4],
            // adv
            [0, 0, 2],
            [0, 1, 4],
            [1, 0, 2],
            [3, 0, 1],
        ];
    }

    /**
     * @dataProvider dataTestAdjacent
     *
     * @param int $x
     * @param int $y
     * @param int $expected
     */
    public function testAdjacent(int $x, int $y, int $expected)
    {
        $field = Field::factory([
            '  # #',
            '     ',
            '  # #',
        ]);

        self::assertCount($expected, $field->adjacent($field->tile($x, $y)));
    }

    public function dataTestVector()
    {
        return [
            // basic
            [0, 0, Point::BOTTOM, 2],
            [0, 2, Point::RIGHT, 1],
            [1, 2, Point::TOP, 2],
            // adv
            [0, 0, Point::LEFT, 2],
            [1, 1, Point::LEFT, 2],
            [1, 1, Point::RIGHT, 2],
        ];
    }

    /**
     * @dataProvider dataTestVector
     *
     * @param int $x
     * @param int $y
     * @param int $direction
     * @param int $expected
     */
    public function testVector(int $x, int $y, int $direction, int $expected)
    {
        $field = Field::factory([
            '   ',
            '   ',
            '  #',
        ]);

        self::assertCount($expected, $field->vector($field->tile($x, $y), $direction));
    }

    public function testWaysCount()
    {
        $field = Field::factory([
            '   ',
            '   ',
            '   ',
        ]);

        self::assertSame(4, $field->waysCount(new Point(1, 1)));
        self::assertSame(3, $field->waysCount(new Point(0, 0)));
        self::assertSame(3, $field->waysCount(new Point(1, 2)));
    }

    public function testLines()
    {
        $field = Field::factory([
            '     ',
            '     ',
            '  # #',
        ]);

        self::assertCount(4, $field->lines(new Point(1, 1)));
        self::assertCount(3, $field->lines(new Point(0, 0)));
        self::assertCount(1, $field->lines(new Point(3, 2)));

        $field = Field::factory([
            ' # ',
            '# #',
            ' # ',
        ]);

        self::assertCount(1, $field->lines(new Point(0, 0)));
        self::assertCount(0, $field->lines(new Point(1, 1)));
    }

    public function dataTestEdges()
    {
        return [
            [0, 0, 1, 4],
            [1, 0, 1, 5],
            [2, 0, 1, 3],
            [0, 0, 2, 8],
            [2, 3, 2, 16],
        ];
    }

    /**
     * @dataProvider dataTestEdges
     *
     * @param int $x
     * @param int $y
     * @param int $distance
     * @param int $expected
     */
    public function testEdges(int $x, int $y, int $distance, int $expected)
    {
        $field = Field::factory([
            ' # # ',
            '     ',
            '     ',
            '     ',
            '     ',
            '     ',
        ]);

        self::assertCount($expected, $field->edges($field->tile($x, $y), $distance));
    }
}
