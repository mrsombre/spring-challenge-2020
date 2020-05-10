<?php
declare(strict_types=1);

class PelletTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $pellet = new Pellet(1, 2);

        self::assertSame(1, $pellet->x);
        self::assertSame(2, $pellet->y);
        self::assertSame(0, $pellet->cost);
        self::assertTrue($pellet->isExists());
        self::assertFalse($pellet->isSuper());
    }

    public function testIsGold()
    {
        $pellet = new Pellet(1, 2);
        $pellet->cost = 10;

        self::assertTrue($pellet->isSuper());
    }
}
