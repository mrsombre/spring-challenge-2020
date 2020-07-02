<?php
declare(strict_types=1);

namespace App;

use ArrayObject;
use SplDoublyLinkedList;
use SplHeap;
use SplObjectStorage;
use SplQueue;
use InvalidArgumentException;
use RuntimeException;
use SplStack;

function debug($var)
{
    if (defined('APP_DEBUG') && !APP_DEBUG) {
        return;
    }
    error_log(var_export($var, true));
}

class Helper
{
    /**
     * Recursive iterator
     *
     * @param iterable $it
     * @return \Generator
     */
    public static function flatten(iterable $it): \Generator
    {
        foreach ($it as $k => $v) {
            if (is_iterable($v)) {
                yield from self::flatten($v);
            } else {
                yield $v;
            }
        }
    }
}

interface CompositeKey
{
    public function ak(): array;

    public function ck(): string;
}

class CompositeKeyHelper
{
    public static function ak(int $a, int $b): array
    {
        return [$a, $b];
    }

    public static function ck(int $a, int $b): string
    {
        return "{$a}.{$b}";
    }
}

interface PositionAware
{
    public function pos(): Point;
}

class Point implements CompositeKey
{
    public const EQUAL = 0;
    public const TOP = 1;
    public const RIGHT = 2;
    public const BOTTOM = 3;
    public const LEFT = 4;
    public const DIAGONAL = 5;

    public const DIRECTIONS = [
        self::TOP,
        self::RIGHT,
        self::BOTTOM,
        self::LEFT,
    ];

    public const DISPLACEMENT = [
        self::TOP => [0, -1],
        self::RIGHT => [1, 0],
        self::BOTTOM => [0, 1],
        self::LEFT => [-1, 0],
    ];

    private $x;
    private $y;
    private $ak;
    private $ck;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
        $this->ak = CompositeKeyHelper::ak($x, $y);
        $this->ck = CompositeKeyHelper::ck($x, $y);
    }

    public function x(): int
    {
        return $this->x;
    }

    public function y(): int
    {
        return $this->y;
    }

    public function ak(): array
    {
        return $this->ak;
    }

    public function ck(): string
    {
        return $this->ck;
    }

    public function isSame(Point $point): bool
    {
        return $this->x === $point->x() && $this->y === $point->y();
    }

    public function distance(Point $point): int
    {
        return abs($this->x - $point->x()) + abs($this->y - $point->y());
    }

    public function verticalDirection(Point $point): int
    {
        $py = $point->y();
        if ($this->y === $py) {
            return self::EQUAL;
        }
        if ($this->y > $py) {
            return self::TOP;
        }
        return self::BOTTOM;
    }

    public function horizontalDirection(Point $point): int
    {
        $px = $point->x();
        if ($this->x === $px) {
            return self::EQUAL;
        }
        if ($this->x > $px) {
            return self::LEFT;
        }
        return self::RIGHT;
    }

    public function direction(Point $point): int
    {
        [$px, $py] = $point->ak();
        if ($this->x === $px) {
            if ($this->y > $py) {
                return self::TOP;
            }
            return self::BOTTOM;
        } elseif ($this->y === $py) {
            if ($this->x > $px) {
                return self::LEFT;
            }
            return self::RIGHT;
        }
        return self::DIAGONAL;
    }

    public function nextPoint(int $direction): Point
    {
        $nx = $this->x + Point::DISPLACEMENT[$direction][0];
        $ny = $this->y + Point::DISPLACEMENT[$direction][1];
        return new Point($nx, $ny);
    }

    public static function opposite(int $direction): int
    {
        switch ($direction) {
            case self::LEFT:
                return self::RIGHT;
            case self::RIGHT:
                return self::LEFT;
            case self::TOP:
                return self::BOTTOM;
            case self::BOTTOM:
                return self::TOP;
        }
        throw new InvalidArgumentException("No opposite side for {$direction}");
    }
}

class Tile extends Point
{
    public const TYPE_WALL = 0;
    public const TYPE_FLOOR = 1;

    private $type;

    public static function factory(int $x, int $y, string $type): Tile
    {
        return new static($x, $y, $type === '#' ? self::TYPE_WALL : self::TYPE_FLOOR);
    }

    public function __construct(int $x, int $y, int $type)
    {
        parent::__construct($x, $y);
        $this->type = $type;
    }

    public function type(): int
    {
        return $this->type;
    }

    public function isWall(): bool
    {
        return $this->type === self::TYPE_WALL;
    }

    public function isFloor(): bool
    {
        return $this->type === self::TYPE_FLOOR;
    }
}

class Pellet extends Tile
{
    public const STATUS_EXISTS = 1;
    public const STATUS_EATEN = 0;

    private $status = self::STATUS_EXISTS;
    private $cost;

    public function __construct(Tile $tile, int $cost)
    {
        if ($tile->isWall()) {
            throw new InvalidArgumentException('Using wall tile for pellet');
        }
        parent::__construct($tile->x(), $tile->y(), self::TYPE_FLOOR);
        $this->cost = $cost;
    }

    public function isExists(): bool
    {
        return $this->status === self::STATUS_EXISTS;
    }

    public function isEaten(): bool
    {
        return $this->status === self::STATUS_EATEN;
    }

    public function isSuper(): bool
    {
        return $this->cost === 10;
    }

    public function cost(): int
    {
        return $this->cost;
    }

    public function eaten(): Pellet
    {
        $this->status = self::STATUS_EATEN;
        return $this;
    }
}

class Vector extends SplDoublyLinkedList
{
    public $direction;
}

class SimulatedPath extends Vector
{
    /** @var \App\Pac */
    public $pac;

    public $deep = 0;
    public $pellets = 0;
    public $steps = 0;
    public $visited = [];
    public $score = 0;

    /** @var \App\Path */
    public $firstPath;

    public $attack = false;
}

class Path extends SplDoublyLinkedList
{
    public const STRAIGHT = 0;
    public const TURN = 1;

    public const DEAD_END = 0;
    public const PATHWAY = 1;

    /** @var \App\Game */
    private $game;
    /** @var \App\Field */
    private $field;

    private $tiles = [];

    private $lineType = self::STRAIGHT;
    private $endingType = self::PATHWAY;

    public function __construct(Game $game, Vector $vector)
    {
        $this->game = $game;
        $this->field = $game->field();

        $this->populate($vector);
        if ($this->field->waysCount($this->top()) === 1) {
            $this->endingType = self::DEAD_END;
        }
    }

    private function populate(Vector $vector)
    {
        /** @var \App\Tile $tile */
        $vector->rewind();
        while ($vector->valid()) {
            $tile = $vector->current();
            // circular
            if (!$this->isEmpty() && $tile === $this->bottom()) {
                return;
            }
            $this->tiles[$tile->ck()] = 1;
            $this->push($tile);
            // cross
            if ($this->field->waysCount($tile) > 2) {
                break;
            }
            $vector->next();
        }

        // turn
        if ($this->field->waysCount($tile) === 2) {
            $this->lineType = self::TURN;
            $lines = $this->field->lines($tile);
            $from = Point::opposite($vector->direction);
            unset($lines[$from]);

            $this->populate(current($lines));
        }
    }

    public function isDeadEnd(): bool
    {
        return $this->endingType === self::DEAD_END;
    }

    public function isTurn(): bool
    {
        return $this->lineType === self::TURN;
    }

    public function tiles(): array
    {
        return $this->tiles;
    }
}

class Pac implements PositionAware, CompositeKey
{
    public const MINE = 1;
    public const ENEMY = 0;

    public const TYPE_ROCK = 'ROCK';
    public const TYPE_PAPER = 'PAPER';
    public const TYPE_SCISSORS = 'SCISSORS';
    public const TYPE_DEAD = 'DEAD';

    public const RULES = [
        self::TYPE_ROCK => self::TYPE_SCISSORS,
        self::TYPE_SCISSORS => self::TYPE_PAPER,
        self::TYPE_PAPER => self::TYPE_ROCK,
    ];

    private $id;
    private $owner;
    private $speedActive = 0;
    private $cooldown = 0;
    private $type;
    private $typeBefore;
    /** @var \App\Point */
    private $pos;
    /** @var \App\Point */
    private $posBefore;
    private $seen = 0;
    private $seenBefore = 0;
    /** @var \App\AbstractOrder */
    private $order;
    /** @var \App\AbstractOrder */
    private $orderBefore;
    private $ak;
    private $ck;

    public function __construct(int $id, int $owner, int $tick, Point $pos, string $type, int $speedActive, int $cooldown)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->pos = $pos;
        $this->type = $type;
        $this->ak = CompositeKeyHelper::ak($owner, $id);
        $this->ck = CompositeKeyHelper::ck($owner, $id);

        $this->update($tick, $pos, $type, $speedActive, $cooldown);
    }

    public function update(int $tick, Point $pos, string $type, int $speedActive, int $cooldown): Pac
    {
        if ($this->seen === $tick) {
            throw new InvalidArgumentException('Only one update on one tick');
        }
        $this->seenBefore = $this->seen;
        $this->seen = $tick;
        $this->posBefore = $this->pos;
        $this->pos = $pos;
        $this->typeBefore = $this->type;
        $this->type = $type;
        $this->speedActive = $speedActive;
        $this->cooldown = $cooldown;
        return $this;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function isMine(): bool
    {
        return $this->owner === self::MINE;
    }

    public function isEnemy(): bool
    {
        return $this->owner === self::ENEMY;
    }

    public function ak(): array
    {
        return $this->ak;
    }

    public function ck(): string
    {
        return $this->ck;
    }

    public function pos(): Point
    {
        return $this->pos;
    }

    public function posBefore(): ?Point
    {
        return $this->posBefore;
    }

    public function moveDirection(): int
    {
        return $this->posBefore->direction($this->pos());
    }

    public function type(): string
    {
        return $this->type;
    }

    public function speedActive(): int
    {
        return $this->speedActive;
    }

    public function isFast(): bool
    {
        return $this->speedActive > 0;
    }

    public function cooldown(): int
    {
        return $this->cooldown;
    }

    public function isPower(): bool
    {
        return $this->cooldown === 0;
    }

    public function isSeen(): bool
    {
        return $this->seen - $this->seenBefore === 1;
    }

    public function isMoving(): bool
    {
        return $this->isSeen() && $this->pos !== $this->posBefore;
    }

    public function compare(Pac $pac): int
    {
        if ($this->type === $pac->type) {
            return 0;
        }
        if (self::RULES[$this->type] === $pac->type) {
            return 1;
        }
        return -1;
    }

    public function assignOrder(?AbstractOrder $order): Pac
    {
        $this->orderBefore = $this->order;
        $this->order = $order;
        return $this;
    }

    /**
     * @return \App\MoveOrder|\App\SwithOrder|\App\SpeedOrder|null
     */
    public function order(): ?AbstractOrder
    {
        return $this->order;
    }

    /**
     * @return \App\MoveOrder|\App\SwithOrder|\App\SpeedOrder|null
     */
    public function orderBefore(): ?AbstractOrder
    {
        return $this->orderBefore;
    }

    public static function stronger(string $type): string
    {
        return array_flip(self::RULES)[$type];
    }
}

class FloorList extends ArrayObject
{
    public function filterPush($newval)
    {
        if ($newval === null) {
            return;
        }
        if (!$newval instanceof Tile) {
            throw new InvalidArgumentException('Only instances of class tile accepted');
        }
        if (!$newval->isFloor()) {
            return;
        }
        parent::offsetSet($newval->ck(), $newval);
    }
}

class Field
{
    private $width;
    private $height;

    /** @var \App\Tile[] */
    private $tiles;

    private $cacheDirections = [];
    private $cacheVectors = [];

    private $isPortal = false;
    /** @var \App\Tile[] */
    private $portals;

    public static function factory(array $raw): Field
    {
        $h = count($raw);
        if ($h === 0) {
            throw new InvalidArgumentException('Zero height field provided');
        }
        $w = strlen($raw[0]);
        if ($w === 0) {
            throw new InvalidArgumentException('Zero width field provided');
        }
        $tiles = [];
        for ($x = 0; $x < $w; $x++) {
            $tiles[$x] = [];
            for ($y = 0; $y < $h; $y++) {
                $tiles[$x][$y] = $raw[$y][$x];
            }
        }
        return new static($tiles);
    }

    public function __construct(array $tiles)
    {
        $this->width = count($tiles);
        if ($this->width === 0) {
            throw new InvalidArgumentException('Zero width field provided');
        }
        $this->height = count($tiles[0]);
        if ($this->height === 0) {
            throw new InvalidArgumentException('Zero height field provided');
        }

        $this->tiles = new ArrayObject;
        $this->portals = new ArrayObject;
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                if (!isset($tiles[$x][$y])) {
                    throw new InvalidArgumentException("Expect value for {$x}.{$y}");
                }
                $tile = Tile::factory($x, $y, $tiles[$x][$y]);
                $this->tiles[$tile->ck()] = $tile;
                if ($tile->isFloor() && ($x === 0 || $x === ($this->width - 1))) {
                    $this->portals[$tile->ck()] = $tile;
                }
            }
        }

        if (count($this->portals)) {
            $this->isPortal = true;
        }
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function tile(int $x, int $y): Tile
    {
        $ck = CompositeKeyHelper::ck($x, $y);
        if (!isset($this->tiles[$ck])) {
            throw new InvalidArgumentException("Tile {$ck} not found");
        }
        return $this->tiles[$ck];
    }

    public function ensureTile(int $x, int $y, $type = Tile::TYPE_FLOOR): ?Tile
    {
        if ($this->isPortal()) {
            $lx = $this->width - 1;
            if ($x < 0) {
                $x = $this->width + $x;
            } elseif ($x > $lx) {
                $x = $this->width - $x;
            }
        }

        try {
            $tile = $this->tile($x, $y);
            if (!$type || $tile->type() === $type) {
                return $tile;
            }
        } catch (InvalidArgumentException $e) {
        }
        return null;
    }

    /**
     * @return \App\Tile[]
     */
    public function tiles(): ArrayObject
    {
        return $this->tiles;
    }

    public function isPortal(): bool
    {
        return $this->isPortal;
    }

    /**
     * @return \App\Tile[]
     */
    public function portals(): ArrayObject
    {
        return $this->portals;
    }

    public function nextTile(Point $point, int $direction): ?Tile
    {
        [$nx, $ny] = $point->nextPoint($direction)->ak();
        return $this->ensureTile($nx, $ny, null);
    }

    /**
     * @param \App\Point $point
     * @return \App\Point[]
     */
    public function adjacent(Point $point): array
    {
        $ck = $point->ck();
        if (!isset($this->cacheDirections[$ck])) {
            $result = [];
            foreach (Point::DIRECTIONS as $direction) {
                if (($tile = $this->nextTile($point, $direction)) && $tile->isFloor()) {
                    $result[$direction] = $tile;
                }
            }
            $this->cacheDirections[$ck] = $result;
        }
        return $this->cacheDirections[$ck];
    }

    public function vector(Point $point, int $direction): Vector
    {
        $ck = $point->ck();
        if (!isset($this->cacheVectors[$ck][$direction])) {
            $result = new Vector;
            $result->direction = $direction;
            $result->setIteratorMode(SplDoublyLinkedList::IT_MODE_KEEP);
            $next = $point;
            $points = [$point->ck() => $point];
            while ($next = $this->nextTile($next, $direction)) {
                if ($next->isWall()) {
                    break;
                }
                if (isset($points[$next->ck()])) {
                    break;
                }
                $result->push($next);
            }
            $this->cacheVectors[$ck][$direction] = $result;
        }
        return $this->cacheVectors[$ck][$direction];
    }

    public function waysCount(Point $point): int
    {
        return count($this->adjacent($point));
    }

    /**
     * @param \App\Point $point
     * @return \App\Vector[]
     */
    public function lines(Point $point): array
    {
        $result = [];
        foreach ($this->adjacent($point) as $direction => $adjacent) {
            $v = $this->vector($point, $direction);
            if ($v->count()) {
                $result[$direction] = $v;
            }
        }
        return $result;
    }

    /**
     * @param \App\Point $point
     * @param int $distance
     * @return \App\Tile[]
     */
    public function edges(Point $point, $distance = 1): FloorList
    {
        [$x, $y] = $point->ak();
        $sx = $x - $distance;
        $sy = $y - $distance;
        $distance = $distance * 2 + 1;
        $dc = $distance - 1;

        $tiles = new FloorList;
        // top
        if ($sy >= 0) {
            for ($x = 0; $x < $distance; $x++) {
                $tiles->filterPush($this->ensureTile($sx + $x, $sy));
            }
        }
        // right
        if (($sx + $dc) < $this->width || $this->isPortal()) {
            for ($y = 1; $y < $dc; $y++) {
                $tiles->filterPush($this->ensureTile($sx + $dc, $sy + $y));
            }
        }
        // bottom
        if (($sy + $dc) < $this->height) {
            for ($x = 0; $x < $distance; $x++) {
                $tiles->filterPush($this->ensureTile($sx + $x, $sy + $dc));
            }
        }
        // left
        if ($sx >= 0 || $this->isPortal()) {
            for ($y = 1; $y < $dc; $y++) {
                $tiles->filterPush($this->ensureTile($sx, $sy + $y));
            }
        }
        return $tiles;
    }
}

class Game
{
    /** @var \App\Field */
    private $field;
    /** @var \App\Radar */
    private $radar;
    /** @var \App\PathFinder */
    private $finder;

    private $ticks = 0;
    private $myScore = 0;
    private $opponentScore = 0;

    /** @var \App\Tick */
    private $tick;
    /** @var \App\Tick */
    private $tickBefore;

    /** @var \App\Pac[] */
    private $pacs;
    /** @var \App\Pellet[] */
    private $pellets;
    /** @var \App\Pellet[] */
    private $supers;
    /** @var \SplObjectStorage */
    private $pacsToSupers;

    public function __construct(Field $field)
    {
        $this->field = $field;
        $this->pellets = new ArrayObject;
        $this->supers = new ArrayObject;
        foreach ($field->tiles() as $tile) {
            if ($tile->isFloor()) {
                $this->pellets[$tile->ck()] = new Pellet($tile, 1);
            }
        }
        $this->pacs = new ArrayObject;
        $this->finder = new PathFinder($this);
    }

    public function field(): Field
    {
        return $this->field;
    }

    public function finder(): PathFinder
    {
        return $this->finder;
    }

    public function turn(int $myScore = 0, int $opponentScore = 0)
    {
        $this->ticks++;
        $this->myScore = $myScore;
        $this->opponentScore = $opponentScore;

        $this->tickBefore = $this->tick;
        $this->tick = new Tick($this->ticks);
    }

    public function findSupers(array $raw)
    {
        foreach ($raw as $data) {
            [$x, $y, $cost] = sscanf($data, '%d %d %d');
            if ($cost > 1) {
                $tile = $this->field->tile($x, $y);
                $this->supers[$tile->ck()] = $this->pellets[$tile->ck()] = new Pellet($tile, $cost);
            }
        }

        $pairs = [];
        /** @var Pellet $pellet */
        foreach ($this->supers as $pellet) {
            /** @var Pac $pac */
            foreach ($this->pacs as $pac) {
                $pairs[$pac->pos()->distance($pellet)][] = ['pac' => $pac, 'pellet' => $pellet];
            }
        }
        ksort($pairs);

        $assignedPacs = new SplObjectStorage;
        $assignedPellets = new SplObjectStorage;
        $pacsCount = count($this->pacs);
        $supersCount = count($this->supers);
        foreach ($pairs as $solutions) {
            foreach ($solutions as $pair) {
                if ($assignedPacs->contains($pair['pac']) || $assignedPellets->contains($pair['pellet'])) {
                    continue;
                }
                $assignedPacs->offsetSet($pair['pac'], $pair['pellet']);
                $assignedPellets->attach($pair['pellet']);

                // no assign enemy supers
                // find opposite
                [$x, $y] = $pair['pellet']->ak();
                $x = $this->field->width() - $x - 1;
                $pellet = $this->pellet($this->field->tile($x, $y));
                $assignedPellets->attach($pellet);
            }
            if ($assignedPacs->count() === $pacsCount || $assignedPellets->count() === $supersCount) {
                break;
            }
        }

        $this->pacsToSupers = $assignedPacs;
    }

    /**
     * @return \App\Pellet[]
     */
    public function supers(): \ArrayObject
    {
        return $this->supers;
    }

    public function pacsToSupers(): \SplObjectStorage
    {
        return $this->pacsToSupers;
    }

    public function tick(): Tick
    {
        return $this->tick;
    }

    public function myScore(): int
    {
        return $this->myScore;
    }

    public function opponentScore(): int
    {
        return $this->opponentScore;
    }

    public function processPac(int $id, int $im, int $x, int $y, string $type, int $speedActive, int $cooldown): ?Pac
    {
        if ($type === Pac::TYPE_DEAD) {
            return null;
        }

        $ck = CompositeKeyHelper::ck($im, $id);
        if (!isset($this->pacs[$ck])) {
            $this->pacs[$ck] = new Pac($id, $im, $this->tick->id(), $this->field->tile($x, $y), $type, $speedActive, $cooldown);
        } else {
            $this->pacs[$ck]->update($this->tick->id(), $this->field->tile($x, $y), $type, $speedActive, $cooldown);
        }
        $this->tick->observePac($this->pacs[$ck]);
        return $this->pacs[$ck];
    }

    public function processPacs(array $raw)
    {
        foreach ($raw as $data) {
            $this->processPac(...sscanf($data, '%d %d %d %d %s %d %d'));
        }
    }

    public function isPacKnown(int $im, int $id): bool
    {
        return isset($this->pacs[CompositeKeyHelper::ck($im, $id)]);
    }

    public function pac(int $im, int $id): Pac
    {
        $ck = CompositeKeyHelper::ck($im, $id);
        if (!isset($this->pacs[$ck])) {
            throw new RuntimeException("Pac {$ck} not found");
        }
        return $this->pacs[$ck];
    }

    /**
     * @return \App\Pac[]
     */
    public function pacs(): ArrayObject
    {
        return $this->pacs;
    }

    public function processPellet(int $x, int $y, int $cost): Pellet
    {
        $ck = CompositeKeyHelper::ck($x, $y);
        if (!isset($this->pellets[$ck]) || ($cost > 1 && !$this->pellets[$ck]->isSuper())) {
            $this->pellets[$ck] = new Pellet($this->field->tile($x, $y), $cost);
        }
        $this->tick->observePellet($this->pellets[$ck]);
        return $this->pellets[$ck];
    }

    public function processPellets(array $raw)
    {
        foreach ($raw as $data) {
            $this->processPellet(...sscanf($data, '%d %d %d'));
        }

        $this->cleanupPelletsIDontSee();
        $this->cleanupPelletsUnderPacs();
    }

    /**
     * delete invisible pellets in pacs visible range
     */
    public function cleanupPelletsIDontSee()
    {
        foreach ($this->tick->visiblePacs() as $pac) {
            if (!$pac->isMine()) {
                continue;
            }

            $paths = $this->field->lines($pac->pos());
            foreach (Helper::flatten($paths) as $point) {
                if (!$this->isPelletKnown($point)) {
                    continue;
                }
                if (!$this->tick->isPelletVisible($point)) {
                    $this->pellet($point)->eaten();
                }
            }
        }
    }

    /**
     * clean all where i see pacs first
     */
    public function cleanupPelletsUnderPacs()
    {
        foreach ($this->tick->visiblePacs() as $pac) {
            if (!$this->isPelletKnown($pac->pos())) {
                continue;
            }
            $this->pellet($pac->pos())->eaten();
        }
    }

    public function isPelletKnown(Point $point): bool
    {
        return isset($this->pellets[CompositeKeyHelper::ck($point->x(), $point->y())]);
    }

    public function pellet(Point $point): Pellet
    {
        $ck = CompositeKeyHelper::ck($point->x(), $point->y());
        if (!$this->isPelletKnown($point)) {
            throw new RuntimeException("Pellet {$ck} not found");
        }
        return $this->pellets[$ck];
    }

    /**
     * @return \App\Pellet[]
     */
    public function pellets(): ArrayObject
    {
        return $this->pellets;
    }

    public function commands(): array
    {
        $result = [];
        /** @var Pac $pac */
        foreach ($this->tick->visiblePacs() as $pac) {
            if (!$pac->isMine()) {
                continue;
            }
            if (($order = $pac->order()) && !$order instanceof NoopOrder) {
                $result[] = str_replace('{id}', $pac->id(), $order->command());
            }
        }
        return $result;
    }
}

class Tick
{
    private $id;

    public $visiblePacsCount = 0;
    /** @var \ArrayObject */
    public $visiblePacs;
    /** @var \ArrayObject */
    public $visiblePacsByPoint;

    public $visiblePelletsCount = 0;
    /** @var \ArrayObject */
    public $visiblePellets;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->visiblePacs = new ArrayObject;
        $this->visiblePacsByPoint = new ArrayObject;
        $this->visiblePellets = new ArrayObject;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function observePac(Pac $pac): Tick
    {
        $this->visiblePacs[$pac->ck()] = $pac;
        $this->visiblePacsByPoint[$pac->pos()->ck()] = $pac;
        $this->visiblePacsCount++;
        return $this;
    }

    public function isPacVisible(int $im, int $id): bool
    {
        return isset($this->visiblePacs[CompositeKeyHelper::ck($im, $id)]);
    }

    public function visiblePac(int $im, int $id): Pac
    {
        $ck = CompositeKeyHelper::ck($im, $id);
        if (!isset($this->visiblePacs[$ck])) {
            throw new InvalidArgumentException("Pac {$ck} not visible");
        }
        return $this->visiblePacs[$ck];
    }

    public function visiblePacInPoint(Point $point): ?Pac
    {
        return $this->visiblePacsByPoint[$point->ck()] ?? null;
    }

    /**
     * @return \App\Pac[]
     */
    public function visiblePacs(): ArrayObject
    {
        return $this->visiblePacs;
    }

    public function observePellet(Point $pellet): Tick
    {
        $this->visiblePellets[$pellet->ck()] = $pellet;
        $this->visiblePelletsCount++;
        return $this;
    }

    public function isPelletVisible(Point $point): bool
    {
        return isset($this->visiblePellets[CompositeKeyHelper::ck($point->x(), $point->y())]);
    }

    public function visiblePellet(Point $point): Pellet
    {
        $ck = CompositeKeyHelper::ck($point->x(), $point->y());
        if (!isset($this->visiblePellets[$ck])) {
            throw new InvalidArgumentException("Pellet {$ck} not visible");
        }
        return $this->visiblePellets[$ck];
    }

    /**
     * @return \App\Pellet[]
     */
    public function visiblePellets(): ArrayObject
    {
        return $this->visiblePellets;
    }
}

class PathFinder
{
    /** @var \App\Game */
    private $game;
    /** @var \App\Field */
    private $field;

    public function __construct(Game $game)
    {
        $this->game = $game;
        $this->field = $game->field();
    }

    /**
     * @param \App\Point $point
     * @return \App\Path[]
     */
    public function paths(Point $point): array
    {
        $lines = $this->field->lines($point);
        $paths = [];
        foreach ($lines as $direction => $vector) {
            $paths[$direction] = new Path($this->game, $vector);
        }
        return $paths;
    }
}

abstract class AbstractOrder
{
    public const MOVE = 'MOVE';
    public const POWER_SPEED = 'SPEED';
    public const POWER_SWITCH = 'SWITCH';

    protected $type;
    protected $value;

    public function command(): string
    {
        $command = $this->type . ' {id}';
        if ($this->value) {
            $command .= ' ' . $this->value;
        }
        return $command;
    }
}

class NoopOrder extends AbstractOrder
{
    public function command(): string
    {
        throw new RuntimeException('No command for noop');
    }
}

class WaitOrder extends NoopOrder
{
}

class SwithOrder extends AbstractOrder
{
    protected $type = self::POWER_SWITCH;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

class SpeedOrder extends AbstractOrder
{
    protected $type = self::POWER_SPEED;
}

class MoveOrder extends AbstractOrder
    implements PositionAware
{
    protected $type = self::MOVE;
    /** @var Point */
    private $point;

    public function __construct(Point $point)
    {
        $this->point = $point;
        $this->value = "{$point->x()} {$point->y()}";
    }

    public function pos(): Point
    {
        return $this->point;
    }
}

class RushSuper extends MoveOrder
{
}

class PathOrder extends MoveOrder
{
    /** @var \App\SimulatedPath */
    public $path;
}

abstract class AbstractStrategy
{
    /** @var SplObjectStorage */
    protected $pacs;
    /** @var \App\Game */
    protected $game;
    /** @var \App\Field */
    protected $field;
    /** @var \App\PathFinder */
    protected $finder;

    public function __construct(SplObjectStorage $pacs, Game $game)
    {
        $this->pacs = $pacs;
        $this->game = $game;
        $this->field = $game->field();
        $this->finder = $game->finder();
    }

    abstract public function exec();

    protected function assign(Pac $pac, AbstractOrder $order)
    {
        if ($this->pacs->offsetGet($pac) instanceof AbstractOrder) {
            throw new InvalidArgumentException('Trying to assign an order twice a tick');
        }
        $pac->assignOrder($order);
        $this->pacs->offsetSet($pac, $order);
    }
}

class NoopStrategy extends AbstractStrategy
{
    public function exec()
    {
        /** @var Pac $pac */
        foreach ($this->pacs as $pac) {
            $this->assign($pac, new NoopOrder);
            debug("Pac {$pac->id()} has no orders, assigned noop fallback");
        }
    }
}

class CloseEnemyStrategy extends AbstractStrategy
{
    public function exec()
    {
        /** @var Pac $pac */
        foreach ($this->pacs as $pac) {
            if ($order = $this->react($pac)) {
                $this->assign($pac, $order);
                continue;
            }
        }
    }

    private function react(Pac $mine): ?AbstractOrder
    {
        if ($order = $this->neighbours($mine)) {
            return $order;
        }
        return null;
    }

    private function neighbours(Pac $mine): ?AbstractOrder
    {
        $adjacent = $this->game->field()->lines($mine->pos());
        $neighbours = [];
        /** @var \App\Tile $point */
        foreach (Helper::flatten($adjacent) as $point) {
            if (isset($this->game->tick()->visiblePacsByPoint[$point->ck()])) {
                $neighbours[] = $this->game->tick()->visiblePacsByPoint[$point->ck()];
            }
        }
        if (count($neighbours) < 1) {
            return null;
        }

        // switch
        /** @var \App\Pac $enemy */
        foreach ($neighbours as $enemy) {
            $distance = $mine->pos()->distance($enemy->pos());
            $mineDirection = $enemy->pos()->direction($mine->pos());

            // weaker
            $cmp = $mine->compare($enemy);
            if ($cmp < 1 && $mine->isPower() && $distance <= 3) {
                debug("Pac {$mine->id()} is weaker than {$enemy->id()}, decided to switch");
                return new SwithOrder(Pac::stronger($enemy->type()));
            }

            // wait if power
            if ($cmp === 1) {
                if (!$enemy->isMoving() || $enemy->moveDirection() === $mineDirection) {
                    if ($enemy->isPower()) {
                        if ($distance === 1 || ($enemy->isFast() && $distance === 2)) {
                            debug("Pac {$mine->id()} is stronger than {$enemy->id()} and power, decided to wait");
                            return new NoopOrder;
                        }
                    } else {
                        if ($distance === 1 || ($enemy->isFast() && $distance === 2)) {
                            debug("Pac {$mine->id()} is stronger than {$enemy->id()} and power, decided to attack");
                            return new MoveOrder($enemy->pos());
                        }
                    }
                }
            }
        }

        return null;
    }
}

class RushSupersStrategy extends AbstractStrategy
{
    public function exec()
    {
        $supers = $this->game->supers();
        $supersCount = $supers->count();
        if (!$supersCount) {
            return;
        }

        /** @var \App\Pac $pac */
        $pacsToSupers = $this->game->pacsToSupers();
        foreach ($this->pacs as $pac) {
            if ($pacsToSupers->offsetExists($pac)) {
                /** @var \App\Pellet $pellet */
                $pellet = $pacsToSupers->offsetGet($pac);
                if ($pellet->isEaten()) {
                    $pacsToSupers->detach($pac);
                    continue;
                }

                if ($order = $this->react($pac, $pellet)) {
                    $this->assign($pac, $order);
                    continue;
                }
            }
        }
    }

    private function react(Pac $mine, Pellet $pellet): ?AbstractOrder
    {
        $distance = $mine->pos()->distance($pellet);
        debug("Pac {$mine->id()} choose nearest super {$pellet->x()}.{$pellet->y()}, distance {$distance}");
        return new RushSuper($pellet);
    }
}

class PriorityPathHeap extends SplHeap
{
    public function compare($value1, $value2)
    {
        return $value1->score <=> $value2->score;
    }
}

class InvalidPathException extends RuntimeException
{
}

class PriorityPathStrategy extends AbstractStrategy
{
    public function exec()
    {
        /** @var Pac $pac */
        foreach ($this->pacs as $pac) {
            if ($pac->order() instanceof PathOrder) {
                $pac->assignOrder(null);
            }
            if ($order = $this->react($pac)) {
                $this->assign($pac, $order);
                continue;
            }
        }
    }

    private function react(Pac $mine): ?AbstractOrder
    {
        $paths = $this->game->finder()->paths($mine->pos());
        if (!count($paths)) {
            return null;
        }

        $pathsHeap = new PriorityPathHeap;
        foreach ($paths as $direction => $path) {
            $simulatedPath = new SimulatedPath;
            $simulatedPath->pac = $mine;
            $simulatedPath->firstPath = $path;

            try {
                $this->priority($path, $simulatedPath);
                $pathsHeap->insert($simulatedPath);
            } catch (InvalidPathException $e) {
            }
        }

        if (!count($pathsHeap)) {
            return null;
        }

        /** @var \App\SimulatedPath $path */
        $path = $pathsHeap->extract();

        if ($mine->isFast()) {
            $way = new SplStack;
            $max = 1;
            foreach ($path as $index => $moveTo) {
                if ($index > $max) {
                    break;
                }
                $way->push($moveTo);
            }

            $moveTo = $way->pop();
            if ($moveTo && $way->valid() && $path->firstPath->isDeadEnd() && !$path->attack && !$this->game->isPelletKnown($moveTo)) {
                $moveTo = $way->pop();
            }
        } else {
            $moveTo = $path->bottom();
        }

        // empty path?
        if (!isset($moveTo)) {
            debug("Empty path WTF???");
            return null;
        }

        debug("Pac {$mine->id()} choosen path to {$path->top()->ck()}, steps {$path->steps}, score {$path->score}, move {$moveTo->ck()}");
        $o = new PathOrder($moveTo);
        $o->path = $path;
        return $o;
    }

    private function priority(Path $path, SimulatedPath &$simulated)
    {
        $score = $this->cost($path, $simulated);
        if ($path->isDeadEnd()) {
            return $score;
        }

        $end = $path->top();
        if ($simulated->deep >= 5 || $simulated->steps > 20) {
            return $score;
        }

        $simulated->deep++;
        $paths = $this->finder->paths($end);

        // remove prev
        if ($path->count() === 1) {
            $prev = $simulated->pac->pos();
        } else {
            $prev = $path->offsetGet($path->count() - 2);
        }

        $from = $end->direction($prev);
        unset($paths[$from]);

        $pathsHeap = new PriorityPathHeap;
        foreach ($paths as $direction => $path) {
            if (isset($simulated->visited[$path->bottom()->ck()])) {
                continue;
            }
            $simulatedPath = clone $simulated;
            try {
                $this->priority($path, $simulatedPath);
                $pathsHeap->insert($simulatedPath);
            } catch (InvalidPathException $e) {
            }
        }
        if (!count($pathsHeap)) {
            return $score;
        }

        $choosen = $pathsHeap->extract();
        $simulated = $choosen;

        return $score + $choosen->score;
    }

    public function cost(Path $path, SimulatedPath $simulated): float
    {
        $score = 0;

        /** @var \App\Pellet $point */
        foreach ($path as $point) {
            $simulated->steps++;

            // pack in path
            if ($pac = $this->game->tick()->visiblePacInPoint($point)) {
                if ($pac->isMine()) {
                    //debug("Visible pac at {$pac->pos()->ck()} is mine, skip occupied path");
                    throw new InvalidPathException;
                } else {
                    if ($simulated->pac->compare($pac) < 1) {
                        throw new InvalidPathException;
                    }
                }
            }

            $simulated->push($point);
            if (isset($simulated->visited[$point->ck()])) {
                continue;
            }

            if ($this->game->isPelletKnown($point)) {
                $simulated->pellets++;
                $pellet = $this->game->pellet($point);
                if ($pellet->isExists()) {
                    $cost = 1;
                    $score += ($cost / $simulated->steps);
                }
            }

            $simulated->visited[$point->ck()] = $point->ck();
        }

        $simulated->score += $score;

        if ($path->isDeadEnd()) {
            $simulated->steps += $path->count();
        }

        return $score;
    }
}

class SpeedStrategy extends AbstractStrategy
{
    private $visibleEnemies = [];

    public function exec()
    {
        /** @var Pac $pac */
        foreach ($this->game->tick()->visiblePacs() as $pac) {
            if ($pac->isEnemy()) {
                $this->visibleEnemies[$pac->pos()->ck()] = $pac;
            }
        }

        /** @var Pac $pac */
        foreach ($this->pacs as $pac) {
            if (!$pac->isPower()) {
                continue;
            }
            if ($order = $this->react($pac)) {
                $this->assign($pac, $order);
                continue;
            }
        }
    }

    private function react(Pac $pac): ?AbstractOrder
    {
        $closestEnemies = $this->closest($pac);
        if (count($closestEnemies)) {
            if (count($closestEnemies) >= 2) {
                return null;
            }
            $enemy = current($closestEnemies);
            if ($pac->compare($enemy) === -1) {
                return null;
            }
        }
        return new SpeedOrder;
    }

    private function closest(Pac $pac)
    {
        $lines = $this->game->field()->lines($pac->pos());
        $result = [];
        /** @var \App\Tile $point */
        foreach (Helper::flatten($lines) as $point) {
            if (isset($this->visibleEnemies[$point->ck()])) {
                $result[$point->ck()] = $this->visibleEnemies[$point->ck()];
            }
        }
        return $result;
    }
}

class Box
{
    /** @var SplObjectStorage */
    private $pacs;
    /** @var AbstractStrategy[] */
    private $strategies;
    private $game;

    public function __construct(Game $game, array $strategies = null)
    {
        $this->pacs = new SplObjectStorage;
        foreach ($game->tick()->visiblePacs() as $pac) {
            if ($pac->isMine()) {
                $this->pacs->attach($pac);
            }
        }

        $this->strategies = new SplQueue;
        if ($strategies === null) {
            $strategies = [
                CloseEnemyStrategy::class,
                SpeedStrategy::class,
                RushSupersStrategy::class,
                PriorityPathStrategy::class,
                NoopStrategy::class,
            ];
        }
        foreach ($strategies as $strategyClass) {
            $this->strategies->enqueue(new $strategyClass($this->pacs, $game));
        }
    }

    public function freeCount(): int
    {
        return $this->pacs->count();
    }

    public function exec()
    {
        /** @var AbstractStrategy $strategy */
        foreach ($this->strategies as $strategy) {
            if (!$this->pacs->count()) {
                return;
            }
            $strategy->exec();
            $this->detachUsedPacs();
        }
    }

    public function detachUsedPacs()
    {
        $delete = new SplObjectStorage;
        foreach ($this->pacs as $pac) {
            if ($this->pacs->getInfo() !== null) {
                $delete->attach($pac);
            }
        }
        $this->pacs->removeAll($delete);
    }
}

class Reader
{
    /**
     * Parsing game field
     */
    public static function readField(): array
    {
        [$w, $h] = fscanf(STDIN, "%d %d");
        $raw = [];
        for ($i = 0; $i < $h; $i++) {
            $raw[$i] = stream_get_line(STDIN, $w + 1, "\n");
        }
        return $raw;
    }

    public static function readScore(): array
    {
        return fscanf(STDIN, "%d %d");
    }

    public static function readInfo(): array
    {
        fscanf(STDIN, "%d", $count);
        $lines = [];
        for ($i = 0; $i < $count; $i++) {
            $lines[] = stream_get_line(STDIN, 64, "\n");
        }
        return $lines;
    }
}

/**
 * Game processing
 */
// @codeCoverageIgnoreStart
if (defined('APP_TEST')) {
    return;
}

// first turn
$field = Field::factory(Reader::readField());
$game = new Game($field);

$score = Reader::readScore();
$game->turn(...$score);

$pacs = Reader::readInfo();
$game->processPacs($pacs);
$pellets = Reader::readInfo();
$game->processPellets($pellets);

$game->findSupers($pellets);
$box = new Box($game);
$box->exec();

$commands = $game->commands();
echo implode(' | ', $commands) . "\n";

// Game loop
while (true) {
    $score = Reader::readScore();
    $game->turn(...$score);

    $pacs = Reader::readInfo();
    $pellets = Reader::readInfo();

    $t = microtime(true);

    $game->processPacs($pacs);
    $game->processPellets($pellets);

    $box = new Box($game);
    $box->exec();

    $commands = $game->commands();

    $tt = number_format(microtime(true) - $t, 5);
    debug("turn time: {$tt}");

    echo implode(' | ', $commands) . "\n";
}
// @codeCoverageIgnoreEnd
