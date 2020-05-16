<?php
declare(strict_types=1);

namespace Test\App;

use App\NoopOrder;
use App\Pac;
use App\Point;
use App\CompositeKeyHelper;

class PacTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $point = new Point(2, 3);
        $pac = new Pac(2, Pac::MINE, 1, $point, Pac::TYPE_ROCK, 2, 3);

        self::assertSame(2, $pac->id());
        self::assertSame(CompositeKeyHelper::ak(1, 2), $pac->ak());
        self::assertSame(CompositeKeyHelper::ck(1, 2), $pac->ck());
        self::assertTrue($pac->isMine());
        self::assertFalse($pac->isEnemy());
        self::assertSame($point, $pac->pos());
        self::assertSame(Pac::TYPE_ROCK, $pac->type());
        self::assertSame(2, $pac->speedActive());
        self::assertSame(3, $pac->cooldown());
    }

    public function testUpdate()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 1, 2);

        $point = new Point(2, 3);
        $pac->update(2, $point, Pac::TYPE_PAPER, 3, 4);
        self::assertSame(1, $pac->id());
        self::assertTrue($pac->isMine());
        self::assertSame($point, $pac->pos());
        self::assertSame(Pac::TYPE_PAPER, $pac->type());
        self::assertSame(3, $pac->speedActive());
        self::assertSame(4, $pac->cooldown());
    }

    public function testNotUpdateTwice()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 1, 1);

        self::expectException(\InvalidArgumentException::class);
        $pac->update(1, new Point(0, 0), Pac::TYPE_PAPER, 0, 0);
    }

    public function testIsFast()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);

        self::assertFalse($pac->isFast());
        $pac->update(2, new Point(0, 0), Pac::TYPE_ROCK, 5, 0);
        self::assertTrue($pac->isFast());
    }

    public function testIsPower()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);

        self::assertTrue($pac->isPower());
        $pac->update(2, new Point(0, 0), Pac::TYPE_ROCK, 0, 10);
        self::assertFalse($pac->isPower());
    }

    public function testIsSeen()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 1, 1);

        $pac->update(2, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);
        $pac->update(3, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);
        self::assertTrue($pac->isSeen());

        $pac->update(5, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);
        self::assertFalse($pac->isSeen());
    }

    public function testIsMoving()
    {
        $point0 = new Point(0, 0);
        $point1 = new Point(0, 1);
        $pac = new Pac(1, 1, 1, $point0, Pac::TYPE_ROCK, 1, 1);

        $pac->update(2, $point0, Pac::TYPE_ROCK, 0, 0);
        $pac->update(3, $point1, Pac::TYPE_ROCK, 0, 0);
        self::assertTrue($pac->isMoving());

        $pac->update(4, $point1, Pac::TYPE_ROCK, 0, 0);
        self::assertFalse($pac->isMoving());

        // test consider not moving as not seen
        $pac->update(5, $point0, Pac::TYPE_ROCK, 0, 0);
        $pac->update(7, $point1, Pac::TYPE_ROCK, 0, 0);
        self::assertFalse($pac->isMoving());
    }

    public function dataTestCompare()
    {
        return [
            [1, Pac::TYPE_ROCK, Pac::TYPE_SCISSORS],
            [0, Pac::TYPE_ROCK, Pac::TYPE_ROCK],
            [-1, Pac::TYPE_ROCK, Pac::TYPE_PAPER],
        ];
    }

    /**
     * @dataProvider dataTestCompare
     *
     * @param int $expected
     * @param string $type0
     * @param string $type1
     */
    public function testCompare(int $expected, string $type0, string $type1)
    {
        $pac0 = new Pac(0, 0, 1, new Point(0, 0), $type0, 0, 0);
        $pac1 = new Pac(0, 1, 1, new Point(1, 1), $type1, 0, 0);

        self::assertSame($expected, $pac0->compare($pac1));
    }

    public function dataTestStronger()
    {
        return [
            [Pac::TYPE_ROCK, Pac::TYPE_SCISSORS],
            [Pac::TYPE_PAPER, Pac::TYPE_ROCK],
            [Pac::TYPE_SCISSORS, Pac::TYPE_PAPER],
        ];
    }

    /**
     * @dataProvider dataTestStronger
     *
     * @param string $expected
     * @param string $type
     */
    public function testStronger(string $expected, string $type)
    {
        self::assertSame($expected, Pac::stronger($type));
    }

    public function testOrder()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 1, 1);

        self::assertNull($pac->order());

        $order = new NoopOrder;
        $pac->assignOrder($order);
        self::assertSame($order, $pac->order());
    }
}
