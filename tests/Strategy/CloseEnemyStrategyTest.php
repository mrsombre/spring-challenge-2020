<?php
declare(strict_types=1);

namespace Test\Strategy;

use App\CloseEnemyStrategy;
use App\Field;
use App\Game;
use App\Box;
use App\MoveOrder;
use App\Pac;
use App\NoopOrder;
use App\SwithOrder;
use Test\GameMaker;

class CloseEnemyStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testWaitIfStronger()
    {
        $game = GameMaker::factory(['#@ #']);

        $mine = $game->pac(Pac::MINE, 0);
        $game->processPac(0, 0, 2, 0, Pac::RULES[$mine->type()], 0, 0);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(NoopOrder::class, $mine->order());
    }

    public function testAttackIfStronger()
    {
        $game = GameMaker::factory(['#@ #']);

        $mine = $game->pac(Pac::MINE, 0);
        $game->processPac(0, 0, 2, 0, Pac::RULES[$mine->type()], 0, 10);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $mine->order());
        self::assertSame('MOVE {id} 2 0', $mine->order()->command());
    }

    public function testSwitchIfWeaker()
    {
        $game = GameMaker::factory(['#@ #']);

        $mine = $game->pac(Pac::MINE, 0);
        $game->processPac(0, 0, 2, 0, Pac::stronger($mine->type()), 0, 0);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(SwithOrder::class, $mine->order());
        self::assertSame('SWITCH {id} SCISSORS', $mine->order()->command());
    }

    public function testSwitchIfSame()
    {
        $game = GameMaker::factory(['#@ #']);

        $mine = $game->pac(Pac::MINE, 0);
        $game->processPac(0, 0, 2, 0, $mine->type(), 0, 0);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(SwithOrder::class, $mine->order());
        self::assertSame('SWITCH {id} PAPER', $mine->order()->command());
    }

    public function testRunIfWeaker()
    {
        $game = GameMaker::factory(['# @  #']);

        $mine = $game->pac(Pac::MINE, 0);
        $game->processPac(0, Pac::ENEMY, 4, 0, Pac::stronger($mine->type()), 0, 0);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertInstanceOf(MoveOrder::class, $mine->order());
    }

    public function testSkipIfCooldown()
    {
        $game = GameMaker::factory(['#@ #']);

        $mine = $game->pac(Pac::MINE, 0);

        $game->turn();
        $game->processPac(0, Pac::MINE, 1, 0, $mine->type(), 0, 10);
        $game->processPac(0, Pac::ENEMY, 2, 0, $mine->type(), 0, 0);

        $box = new Box($game, [
            CloseEnemyStrategy::class,
        ]);
        $box->exec();

        self::assertNull($mine->order());
    }
}
