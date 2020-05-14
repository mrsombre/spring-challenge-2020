<?php
declare(strict_types=1);

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

    public function testAttachPellets()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);

        $game->turn(0, 0);
        $pellet = $game->processPellet(0, 0, 1);
        $game->attachPellets();
        self::assertSame(1, $game->possiblePellets()->count());
        self::assertTrue($game->possiblePellets()->contains($pellet));

        $game->turn(0, 0);
        $super = $game->processPellet(1, 0, 10);
        $game->attachPellets();
        self::assertSame(1, $game->superPellets()->count());
        self::assertSame(2, $game->possiblePellets()->count());
        self::assertTrue($game->superPellets()->contains($super));
    }

    public function testDetachPellets()
    {
        $field = Field::factory(['    ']);
        $game = new Game($field);

        $game->turn(0, 0);
        $pellet = $game->processPellet(0, 0, 1);
        $game->processPellet(1, 0, 1);
        $game->processPellet(2, 0, 1);
        $game->attachPellets();
        self::assertSame(3, $game->possiblePellets()->count());

        $game->turn(0, 0);
        $super = $game->processPellet(3, 0, 10);
        $game->attachPellets();

        $game->pellet($field->tile(0, 0))->eaten();
        $game->pellet($field->tile(1, 0))->eaten();
        $game->pellet($field->tile(2, 0))->eaten();
        $game->pellet($field->tile(3, 0))->eaten();
        $game->detachPellets();
        self::assertSame(0, $game->possiblePellets()->count());
        self::assertFalse($game->possiblePellets()->contains($pellet));
        self::assertFalse($game->superPellets()->contains($super));
    }

    public function testCleanupPelletsUnderPacs()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);

        $game->turn(0, 0);
        $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPellet(0, 0, 1);
        $game->processPellet(1, 0, 1);
        $game->attachPellets();
        self::assertSame(2, $game->possiblePellets()->count());
        self::assertFalse($game->pellet($field->tile(0, 0))->isEaten());
        self::assertFalse($game->pellet($field->tile(1, 0))->isEaten());

        $game->cleanupPelletsUnderPacs();
        self::assertTrue($game->pellet($field->tile(0, 0))->isEaten());
        self::assertFalse($game->pellet($field->tile(1, 0))->isEaten());
    }

    public function testCleanupPelletsIDontSee()
    {
        $field = Field::factory([
            ' # ',
            '   ',
        ]);
        $game = new Game($field);

        $game->turn(0, 0);
        $game->processPellet(0, 1, 1);
        $game->processPellet(2, 0, 1);
        $game->attachPellets();
        self::assertSame(2, $game->possiblePellets()->count());
        self::assertFalse($game->pellet($field->tile(0, 1))->isEaten());
        self::assertFalse($game->pellet($field->tile(2, 0))->isEaten());

        $game->turn(0, 0);
        $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->cleanupPelletsIDontSee();
        self::assertTrue($game->pellet($field->tile(0, 1))->isEaten());
        self::assertFalse($game->pellet($field->tile(2, 0))->isEaten());
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
