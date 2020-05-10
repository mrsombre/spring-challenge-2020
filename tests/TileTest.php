<?php
declare(strict_types=1);

class TileTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $tile = Tile::factory(1, 2, ' ');

        self::assertSame(1, $tile->x);
        self::assertSame(2, $tile->y);

        self::assertTrue($tile->isFloor());
        self::assertFalse($tile->isWall());

        $tile = Tile::factory(3, 4, '#');

        self::assertSame(3, $tile->x);
        self::assertSame(4, $tile->y);

        self::assertTrue($tile->isWall());
        self::assertFalse($tile->isFloor());
    }
}
