<?php
declare(strict_types=1);

class FieldTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $field = new Field([
            new Pellet(0, 0),
            new Wall(1, 0),
        ]);

        self::assertSame(2, $field->w);
        self::assertSame(1, $field->h);
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

    public function testProcessPac()
    {
        $field = Field::factory(['  ']);

        $field->processPac(0, 1, 1, 0, '', 0, 0);
        $pac = $field->pac(1, 0);
        self::assertSame(1, $pac->pos->x);
        self::assertSame(0, $pac->pos->y);
        self::assertSame($pac, $field->visiblePacs[1][0]);

        $field->processPac(0, 1, 0, 0, '', 0, 0);
        self::assertSame($field->pellet(0, 0), $pac->pos);
    }

    public function testProcessPellet()
    {
        $field = Field::factory(['  ']);

        $field->processPellet(1, 0, 10);

        $pellet = $field->pellet(1, 0);
        self::assertSame(1, $pellet->x);
        self::assertSame(0, $pellet->y);
        self::assertTrue($pellet->isSuper());
        self::assertTrue($pellet->isExists());
    }
}
