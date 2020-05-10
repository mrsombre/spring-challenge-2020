<?php
declare(strict_types=1);

class BoxTest extends \PHPUnit\Framework\TestCase
{
    public function testInit()
    {
        $game = new Game;
        $field = Field::factory([' #']);
        $box = new Box($field, $game);

        self::assertCount(1, $box->pellets);
    }

    public function testCleanup()
    {
        $game = new Game;
        $field = Field::factory([
            ' #',
            '  ',
        ]);

        $pellet0 = $field->pellet(0, 0);
        self::assertTrue($pellet0->isExists());

        $field->processPellet(0, 1, 1);
        $field->processPac(0, 1, 0, 0, '', 0, 0);
        $order = new Order($pellet0);
        $pac0 = $field->pac(1, 0);
        $pac0->order = $order;

        $box = new Box($field, $game);
        $box->cleanup();

        self::assertFalse($pellet0->isExists());
        self::assertArrayNotHasKey(1, $box->pellets);
        self::assertArrayNotHasKey(0, $box->pellets[0]);

        self::assertNull($pac0->order);
        self::assertCount(1, $box->pacs);
    }

    public function testRushSuper()
    {
        $game = new Game;
        $field = Field::factory([
            '   ',
            '   ',
            '   ',
        ]);

        $field->processPellet(1, 0, 10);
        $field->processPellet(0, 2, 10);

        $field->processPac(0, 1, 0, 0, '', 0, 0);
        $field->processPac(1, 1, 2, 2, '', 0, 0);

        $box = new Box($field, $game);
        $box->cleanup();
        $box->runToSuper();

        self::assertSame($field->pac(1, 0)->order->pos, $field->pellet(1, 0));
        self::assertSame($field->pac(1, 1)->order->pos, $field->pellet(0, 2));
    }
}
