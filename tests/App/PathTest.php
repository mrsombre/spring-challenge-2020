<?php
declare(strict_types=1);

namespace Test\App;

use App\Path;
use App\Field;
use App\Game;
use App\Point;

class PathTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $field = Field::factory([
            '#   #',
        ]);
        $game = new Game($field);

        $lines = $field->lines($field->tile(1, 0));
        $path = new Path($game, $lines[Point::RIGHT]);

        self::assertSame($field->tile(2, 0), $path->bottom());
        self::assertSame($field->tile(3, 0), $path->top());
        self::assertTrue($path->isDeadEnd());
    }

    public function testCorner()
    {
        $field = Field::factory([
            '#   #',
            '### #',
        ]);
        $game = new Game($field);

        $lines = $field->lines($field->tile(1, 0));
        $path = new Path($game, $lines[Point::RIGHT]);

        self::assertSame($field->tile(3, 1), $path->top());
        self::assertTrue($path->isDeadEnd());

        $field = Field::factory([
            '#   #',
            '# ###',
        ]);
        $game = new Game($field);

        $lines = $field->lines($field->tile(1, 1));
        $path = new Path($game, $lines[Point::TOP]);

        self::assertSame($field->tile(3, 0), $path->top());
        self::assertTrue($path->isDeadEnd());
    }

    public function testCircular()
    {
        $field = Field::factory([
            '#   #',
            '# # #',
            '#   #',
        ]);
        $game = new Game($field);

        $lines = $field->lines($field->tile(1, 0));
        $path = new Path($game, $lines[Point::RIGHT]);

        self::assertSame($field->tile(1, 0), $path->top());
        self::assertFalse($path->isDeadEnd());
    }

    public function testStopOnCross()
    {
        $field = Field::factory([
            '#    #',
            '### ##',
        ]);
        $game = new Game($field);

        $lines = $field->lines($field->tile(1, 0));
        $path = new Path($game, $lines[Point::RIGHT]);

        self::assertSame($field->tile(3, 0), $path->top());
        self::assertFalse($path->isDeadEnd());
    }
}
