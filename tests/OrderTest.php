<?php
declare(strict_types=1);

class OrderTest extends \PHPUnit\Framework\TestCase
{
    public function testNoop()
    {
        self::expectException(RuntimeException::class);

        $order = new NoopOrder;
        $order->command();
    }

    public function testSwitch()
    {
        $order = new SwithOrder(Pac::TYPE_ROCK);
        self::assertSame('SWITCH {id} ROCK', $order->command());
    }

    public function testMove()
    {
        $order = new MoveOrder(new Point(0, 1));
        self::assertSame('MOVE {id} 0 1', $order->command());
    }
}
