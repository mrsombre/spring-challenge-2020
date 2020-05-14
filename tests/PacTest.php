<?php
declare(strict_types=1);

class PacTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $point = new Point(2, 3);
        $pac = new Pac(1, Pac::MINE, 1, $point, Pac::TYPE_ROCK, 2, 3);

        self::assertSame(1, $pac->id());
        self::assertTrue($pac->isMine());
        self::assertFalse($pac->isEnemy());
        self::assertSame($point, $pac->pos());
        self::assertSame(Pac::TYPE_ROCK, $pac->type());
        self::assertSame(2, $pac->speedActive());
        self::assertSame(3, $pac->cooldown());
    }

    public function testObserve()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 1, 1);

        $point = new Point(2, 3);
        $pac->update(2, $point, Pac::TYPE_PAPER, 0, 0);
        self::assertTrue($pac->isSeen());
        self::assertSame($point, $pac->pos());
        self::assertTrue($pac->isMoving());
        self::assertSame(Pac::TYPE_PAPER, $pac->type());
        self::assertFalse($pac->isFast());
        self::assertTrue($pac->isPower());
    }

    public function testIsSeen()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 1, 1);

        $pac->update(2, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);
        $pac->update(3, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);
        self::assertTrue($pac->isSeen());

        $pac->update(10, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);
        self::assertFalse($pac->isSeen());
    }

    public function testIsMoving()
    {
        $pac = new Pac(1, 1, 1, new Point(0, 0), Pac::TYPE_ROCK, 1, 1);

        $pac->update(2, new Point(0, 0), Pac::TYPE_ROCK, 0, 0);
        $point = new Point(0, 1);
        $pac->update(3, $point, Pac::TYPE_ROCK, 0, 0);
        self::assertTrue($pac->isMoving());

        $pac->update(4, $point, Pac::TYPE_ROCK, 0, 0);
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
     * @param int $expected
     * @param string $type0
     * @param string $type1
     */
    public function testCompare(int $expected, string $type0, string $type1)
    {
        $pac0 = new Pac(0, 1, 1, new Point(0, 0), $type0, 0, 0);
        $pac1 = new Pac(0, 1, 1, new Point(1, 1), $type1, 0, 0);

        self::assertSame($expected, $pac0->compare($pac1));
    }
}
