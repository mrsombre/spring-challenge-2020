<?php
declare(strict_types=1);

namespace Test\App;

use App\Pac;
use App\Field;
use App\Game;
use App\Point;
use Test\GameMaker;

class RadarTest extends \PHPUnit\Framework\TestCase
{
    public function testBasic()
    {
        $field = Field::factory([' ']);
        $game = new Game($field);
        $radar = $game->radar();

        self::assertSame($game->pellets(), $radar->pellets());
    }

    public function testAttachPellets()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);
        $radar = $game->radar();
        $game->turn(0, 0);

        $pellet = $game->processPellet(0, 0, 1);
        $radar->attachPellets();
        self::assertSame(2, $radar->pellets()->count());
        self::assertArrayHasKey($pellet->ck(), $radar->pellets());

        $game->turn(0, 0);
        $super = $game->processPellet(1, 0, 10);
        $radar->attachPellets();
        self::assertSame(1, $radar->supers()->count());
        self::assertArrayHasKey($super->ck(), $radar->supers());
    }

    public function testDetachPellets()
    {
        $field = Field::factory(['   ']);
        $game = new Game($field);
        $radar = $game->radar();
        $game->turn(0, 0);

        $pellet0 = $game->processPellet(0, 0, 1);
        $pellet1 = $game->processPellet(1, 0, 1);
        $pellet2 = $game->processPellet(2, 0, 1);
        $radar->attachPellets();
        self::assertSame(3, $radar->pellets()->count());

        $game->turn(0, 0);
        $pellet0->eaten();
        $pellet2->eaten();
        $radar->detachPellets();
        self::assertSame(1, $radar->pellets()->count());
        self::assertSame(1, $game->pellets()->count());
        self::assertArrayNotHasKey($pellet0->ck(), $radar->pellets());
        self::assertArrayNotHasKey($pellet2->ck(), $radar->pellets());
        self::assertArrayHasKey($pellet1->ck(), $radar->pellets());
    }

    public function testCleanupPelletsUnderPacs()
    {
        $field = Field::factory(['  ']);
        $game = new Game($field);
        $radar = $game->radar();
        $game->turn(0, 0);

        $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $game->processPellet(0, 0, 1);
        $game->processPellet(1, 0, 1);
        $radar->attachPellets();
        self::assertSame(2, $radar->pellets()->count());
        self::assertFalse($game->pellet($field->tile(0, 0))->isEaten());
        self::assertFalse($game->pellet($field->tile(1, 0))->isEaten());

        $radar->cleanupPelletsUnderPacs();
        self::assertTrue($game->pellet($field->tile(0, 0))->isEaten());
        self::assertFalse($game->pellet($field->tile(1, 0))->isEaten());
    }

    public function testCleanupPelletsIDontSee()
    {
        $field = Field::factory([
            ' # #',
            '    ',
        ]);
        $game = new Game($field);
        $radar = $game->radar();
        $game->turn(0, 0);

        $game->processPellet(0, 1, 1);
        $game->processPellet(2, 0, 1);
        $radar->attachPellets();
        self::assertFalse($game->pellet($field->tile(0, 1))->isEaten());
        self::assertFalse($game->pellet($field->tile(2, 0))->isEaten());

        $game->turn(0, 0);
        $game->processPac(0, 1, 0, 0, Pac::TYPE_ROCK, 0, 0);
        $radar->cleanupPelletsIDontSee();
        self::assertTrue($game->pellet($field->tile(0, 1))->isEaten());
        self::assertFalse($game->pellet($field->tile(2, 0))->isEaten());
    }

    public function testClosestPellet()
    {
        $game = GameMaker::factory([
            '#.   #',
            '#    #',
            '#    #',
            '#   .#',
        ]);
        $radar = $game->radar();

        self::assertArrayHasKey('1.0', $radar->closestPellets(new Point(2, 1)));
        self::assertArrayNotHasKey('4.3', $radar->closestPellets(new Point(2, 1)));
        self::assertArrayHasKey('4.3', $radar->closestPellets(new Point(3, 2)));
        self::assertCount(2, $radar->closestPellets(new Point(1, 3)));
    }

    public function testClosestPelletEmptyField()
    {
        $game = GameMaker::factory([
            '    ',
            '    ',
            '    ',
        ]);
        $radar = $game->radar();

        self::assertCount(0, $radar->closestPellets(new Point(1, 1)));
    }
}
