<?php
declare(strict_types=1);

namespace Test\App;

use App\Game;
use App\Pellet;
use App\Field;
use App\Pac;
use App\NoopOrder;
use App\SwithOrder;

class GameTest extends \PHPUnit\Framework\TestCase
{
    public function testSimple()
    {
        $field = Field::factory([' ']);
        $game = new Game($field);
        $game->turn(2, 3);

        self::assertSame($field, $game->field());
        self::assertSame(2, $game->myScore());
        self::assertSame(3, $game->opponentScore());
        self::assertSame(1, $game->tick()->id());
    }

    public function testProcessPacs()
    {
        $field = Field::factory([' ']);
        $game = new Game($field);
        $game->turn(2, 3);

        $raw = [
            '0 1 0 0 PAPER 4 5',
        ];
        $game->processPacs($raw);

        self::assertInstanceOf(Pac::class, $game->pac(1, 0));
        self::assertInstanceOf(\ArrayObject::class, $game->pacs());
        self::assertSame(1, $game->pacs()->count());
    }

    public function testProcessPac()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);
        $game->turn(2, 3);

        $pac = $game->processPac(0, 1, 0, 0, Pac::TYPE_PAPER, 4, 5);
        self::assertTrue($game->isPacKnown(1, 0));
        self::assertSame(0, $game->tick()->visiblePac(1, 0)->id());

        self::assertSame(0, $pac->id());
        self::assertTrue($pac->isMine());
        self::assertSame($field->tile(0, 0), $pac->pos());
        self::assertSame(Pac::TYPE_PAPER, $pac->type());
        self::assertSame(4, $pac->speedActive());
        self::assertSame(5, $pac->cooldown());

        $game->turn(2, 3);
        $game->processPac(0, 1, 1, 0, Pac::TYPE_ROCK, 6, 7);
        self::assertSame($field->tile(1, 0), $pac->pos());
        self::assertSame(Pac::TYPE_ROCK, $pac->type());
        self::assertSame(6, $pac->speedActive());
        self::assertSame(7, $pac->cooldown());
    }

    public function testProcessPellets()
    {
        $field = Field::factory([' ']);
        $game = new Game($field);
        $game->turn(2, 3);

        $raw = [
            '0 0 10',
        ];
        $game->processPellets($raw);

        self::assertInstanceOf(Pellet::class, $game->pellet($field->tile(0, 0)));
        self::assertInstanceOf(\ArrayObject::class, $game->pellets());
        self::assertSame(1, $game->pellets()->count());
    }

    public function testProcessPellet()
    {
        $field = Field::factory([' ']);
        $game = new Game($field);
        $game->turn(2, 3);

        $pellet = $game->processPellet(0, 0, 10);
        self::assertTrue($game->isPelletKnown($field->tile(0, 0)));
        self::assertTrue($game->tick()->visiblePellet($field->tile(0, 0))->isSuper());

        self::assertTrue($field->tile(0, 0)->isSame($pellet));
        self::assertTrue($pellet->isSuper());
        self::assertTrue($pellet->isExists());
    }

    public function testCommands()
    {
        $field = Field::factory([' ']);
        $game = new Game($field);
        $game->turn(0, 0);

        $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->pac(1, 0)->assignOrder(new NoopOrder);
        $commands = $game->commands();
        self::assertCount(0, $commands);

        $game->processPac(1, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->pac(1, 1)->assignOrder(new SwithOrder(Pac::TYPE_SCISSORS));
        $commands = $game->commands();
        self::assertCount(1, $commands);
        self::assertSame('SWITCH 1 SCISSORS', current($commands));
    }
}
