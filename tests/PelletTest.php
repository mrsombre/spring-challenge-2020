<?php
declare(strict_types=1);

class PelletTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $pellet = new Pellet(new Point(1, 2), 1);

        self::assertSame(1, $pellet->x());
        self::assertSame(2, $pellet->y());
        self::assertTrue($pellet->isExists());
    }

    public function testIsSuper()
    {
        $pellet = new Pellet(new Point(1, 2), 10);

        self::assertTrue($pellet->isSuper());
    }

    public function testEaten()
    {
        $pellet = new Pellet(new Point(1, 2), 1);
        self::assertTrue($pellet->isExists());

        $pellet->eaten();
        self::assertTrue($pellet->isEaten());
    }
}
