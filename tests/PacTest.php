<?php
declare(strict_types=1);

class PacTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $pac = new Pac(0, true);

        self::assertSame(0, $pac->id);
        self::assertTrue($pac->isMine);
    }

    public function testIsStuck()
    {
        $pac = new Pac(0, true);

        $position = new Pellet(0, 0);
        $pac->updatePosition($position);
        $pac->updatePosition($position);
        self::assertTrue($pac->isStuck());
    }

    public function testIsMoving()
    {
        $pac = new Pac(0, true);

        // no orders
        $position = new Pellet(0, 0);
        $pac->updatePosition($position);
        self::assertFalse($pac->isMoving());

        // stuck
        $position1 = new Pellet(0, 1);
        $order = new Order($position1);
        $pac->updateOrder($order);
        $pac->updatePosition($position);
        self::assertTrue($pac->isStuck());
        self::assertFalse($pac->isMoving());

        // on place
        $pac->updatePosition($position1);
        self::assertFalse($pac->isStuck());
        self::assertFalse($pac->isMoving());

        // actually moving
        $pac->updatePosition($position);
        self::assertTrue($pac->isMoving());
    }
}
