<?php
declare(strict_types=1);

function debug($var)
{
    if (defined('APP_DEBUG') && !APP_DEBUG) {
        return;
    }
    error_log(var_export($var, true));
}

class Helper
{
    public static function flatten(iterable $it): Generator
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

class Point
{
    public const TOP = 0;
    public const RIGHT = 1;
    public const BOTTOM = 2;
    public const LEFT = 3;

    public const DIAGONAL = 0;

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

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function x(): int
    {
        return $this->x;
    }

    public function y(): int
    {
        return $this->y;
    }

    public function isSame(Point $point): bool
    {
        return $this->x === $point->x && $this->y === $point->y;
    }

    public function distance(Point $point): int
    {
        return abs($this->x - $point->x) + abs($this->y - $point->y);
    }

    public function direction(Point $point): int
    {
        if ($this->x === $point->x) {
            if ($this->y > $point->y) {
                return self::TOP;
            }
            return self::BOTTOM;
        } elseif ($this->y === $point->y) {
            if ($this->x > $point->x) {
                return self::LEFT;
            }
            return self::RIGHT;
        }
        return self::DIAGONAL;
    }
}

class Tile extends Point
{
    public const TYPE_WALL = 0;
    public const TYPE_FLOOR = 1;

    private $type;

    public static function factory(int $x, int $y, string $type): Tile
    {
        return new static($x, $y, $type === ' ' ? self::TYPE_FLOOR : self::TYPE_WALL);
    }

    public function __construct(int $x, int $y, int $type)
    {
        parent::__construct($x, $y);
        $this->type = $type;
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

class Pellet extends Point
{
    public const STATUS_EXISTS = 1;
    public const STATUS_EATEN = 0;

    private $status = self::STATUS_EXISTS;
    private $cost;

    public function __construct(Point $point, int $cost)
    {
        parent::__construct($point->x(), $point->y());
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

    public function eaten(): Pellet
    {
        $this->status = self::STATUS_EATEN;
        return $this;
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
        throw new \RuntimeException('No command for noop');
    }
}

class SwithOrder extends AbstractOrder
{
    protected $type = self::POWER_SWITCH;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

class MoveOrder extends AbstractOrder
{
    protected $type = self::MOVE;
    /** @var \Point */
    private $point;

    public function __construct(Point $point)
    {
        $this->point = $point;
        $this->value = "{$point->x()} {$point->y()}";
    }
}

class Pac
{
    public const MINE = 1;
    public const ENEMY = 0;

    public const TYPE_ROCK = 'ROCK';
    public const TYPE_PAPER = 'PAPER';
    public const TYPE_SCISSORS = 'SCISSORS';

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
    /** @var \Point */
    private $pos;
    /** @var \Point */
    private $posBefore;
    private $seen = 0;
    private $seenBefore = 0;

    /** @var \AbstractOrder */
    public $order;
    /** @var \AbstractOrder */
    public $orderBefore;

    public function __construct(int $id, int $owner, int $tick, Point $pos, string $type, int $speedActive, int $cooldown)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->pos = $pos;
        $this->type = $type;

        $this->update($tick, $pos, $type, $speedActive, $cooldown);
    }

    public function update(int $tick, Point $pos, string $type, int $speedActive, int $cooldown): Pac
    {
        if ($this->seen === $tick) {
            throw new \InvalidArgumentException('Only one update on one tick');
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

    public function pos(): Point
    {
        return $this->pos;
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
        return $this->pos !== $this->posBefore;
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

    public function assignOrder(AbstractOrder $order): Pac
    {
        $this->orderBefore = $this->order;
        $this->order = $order;
        return $this;
    }

    public function order(): ?AbstractOrder
    {
        return $this->order;
    }

    public static function stronger(string $type): string
    {
        return array_flip(self::RULES)[$type];
    }
}

class Field
{
    private $width = 0;
    private $height = 0;

    /** @var \SplFixedArray */
    private $tiles;
    /** @var \SplFixedArray */
    private $cacheDirections;
    /** @var \SplFixedArray */
    private $cacheVectors;

    public static function factory(array $raw): Field
    {
        $h = count($raw);
        if ($h === 0) {
            throw new \InvalidArgumentException('Zero height field provided');
        }
        $w = strlen($raw[0]);
        if ($w === 0) {
            throw new \InvalidArgumentException('Zero width field provided');
        }
        $tiles = [];
        for ($y = 0; $y < $h; $y++) {
            if (strlen($raw[$y]) !== $w) {
                throw new \InvalidArgumentException('Variable width field passed');
            }
            for ($x = 0; $x < $w; $x++) {
                $tiles[] = Tile::factory($x, $y, $raw[$y][$x]);
            }
        }
        return new static($tiles, $w, $h);
    }

    public function __construct(array $tiles, int $width, int $height)
    {
        $this->tiles = new SplFixedArray($width);
        $this->cacheDirections = new SplFixedArray($width);
        $this->cacheVectors = new SplFixedArray($width);

        for ($x = 0; $x < $width; $x++) {
            $this->tiles[$x] = new SplFixedArray($height);
            $this->cacheDirections[$x] = new SplFixedArray($height);
            $this->cacheVectors[$x] = new SplFixedArray($height);
            for ($y = 0; $y < $height; $y++) {
                $this->cacheVectors[$x][$y] = new SplFixedArray(4);
            }
        }

        /** @var \Tile $tile */
        foreach ($tiles as $tile) {
            if (!$tile instanceof \Tile) {
                $type = get_class($tile);
                throw new \InvalidArgumentException("Invalid tile type: {$type}");
            }
            $this->tiles[$tile->x()][$tile->y()] = $tile;
        }

        $this->width = $width;
        $this->height = $height;
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
        if (!isset($this->tiles[$x][$y])) {
            throw new \InvalidArgumentException("Tile {$x}.{$y} not found");
        }
        return $this->tiles[$x][$y];
    }

    public function nextTile(Point $point, int $direction): ?Tile
    {
        $nx = $point->x() + Point::DISPLACEMENT[$direction][0];
        $ny = $point->y() + Point::DISPLACEMENT[$direction][1];
        return isset($this->tiles[$nx][$ny]) ? $this->tiles[$nx][$ny] : null;
    }

    /**
     * @param \Point $point
     * @return Point[]
     */
    public function adjacent(Point $point): array
    {
        if (!isset($this->cacheDirections[$point->x()][$point->y()])) {
            $result = [];
            foreach (Point::DIRECTIONS as $direction) {
                if (($tile = $this->nextTile($point, $direction)) && $tile->isFloor()) {
                    $result[$direction] = $tile;
                }
            }
            $this->cacheDirections[$point->x()][$point->y()] = $result;
        }
        return $this->cacheDirections[$point->x()][$point->y()];
    }

    public function vector(Point $point, int $direction): SplDoublyLinkedList
    {
        if (!isset($this->cacheVectors[$point->x()][$point->y()][$direction])) {
            $result = new SplDoublyLinkedList;
            $result->setIteratorMode(SplDoublyLinkedList::IT_MODE_KEEP);
            $next = $point;
            while ($next = $this->nextTile($next, $direction)) {
                if ($next->isWall()) {
                    break;
                }
                $result->push($next);
            }
            $this->cacheVectors[$point->x()][$point->y()][$direction] = $result;
        }
        return $this->cacheVectors[$point->x()][$point->y()][$direction];
    }

    public function pathsCount(Point $point): int
    {
        return count($this->adjacent($point));
    }

    public function paths(Point $point): array
    {
        $result = [];
        foreach (Point::DIRECTIONS as $direction) {
            $v = $this->vector($point, $direction);
            if ($v->count()) {
                $result[$direction] = $v;
            }
        }
        return $result;
    }
}

class Game
{
    /** @var \Field */
    private $field;

    private $ticks = 0;
    private $myScore = 0;
    private $opponentScore = 0;

    /** @var \Tick */
    private $tick;
    /** @var \Tick */
    private $tickBefore;

    private $pacs;
    private $pellets;

    /** @var \SplObjectStorage */
    private $cachePellets;
    /** @var \SplObjectStorage */
    private $cacheSupers;

    public function __construct(Field $field)
    {
        $this->field = $field;
        $this->cachePellets = new SplObjectStorage;
        $this->cacheSupers = new SplObjectStorage;
    }

    public function field(): Field
    {
        return $this->field;
    }

    public function turn(int $myScore, int $opponentScore)
    {
        $this->ticks++;
        $this->myScore = $myScore;
        $this->opponentScore = $opponentScore;

        $this->tickBefore = $this->tick;
        $this->tick = new Tick($this->ticks);
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

    public function processPac(int $id, int $im, int $x, int $y, string $type, int $speedActive, int $cooldown): Pac
    {
        if (!isset($this->pacs[$im][$id])) {
            $this->pacs[$im][$id] = new Pac($id, $im, $this->tick->id(), $this->field->tile($x, $y), $type, $speedActive, $cooldown);
        } else {
            $this->pacs[$im][$id]->update($this->tick->id(), $this->field->tile($x, $y), $type, $speedActive, $cooldown);
        }
        $this->tick->appendPac($this->pacs[$im][$id]);
        return $this->pacs[$im][$id];
    }

    public function processPacs(array $raw)
    {
        foreach ($raw as $data) {
            $this->processPac(...sscanf($data, '%d %d %d %d %s %d %d'));
        }
    }

    public function isPacKnown(int $im, int $id): bool
    {
        return isset($this->pacs[$im][$id]);
    }

    public function pac(int $im, int $id): Pac
    {
        if (!isset($this->pacs[$im][$id])) {
            throw new \RuntimeException("Pac {$im}.{$id} not found");
        }
        return $this->pacs[$im][$id];
    }

    public function processPellet(int $x, int $y, int $cost): Pellet
    {
        if (!isset($this->pellets[$x][$y])) {
            $this->pellets[$x][$y] = new Pellet($this->field->tile($x, $y), $cost);
        }
        $this->tick->appendPellet($this->pellets[$x][$y]);
        return $this->pellets[$x][$y];
    }

    public function processPellets(array $raw)
    {
        foreach ($raw as $data) {
            $this->processPellet(...sscanf($data, '%d %d %d'));
        }
    }

    public function isPelletKnown(Point $point): bool
    {
        return isset($this->pellets[$point->x()][$point->y()]);
    }

    public function pellet(Point $point): Pellet
    {
        if (!isset($this->pellets[$point->x()][$point->y()])) {
            throw new \RuntimeException("Pellet {$point->x()}.{$point->y()} not found");
        }
        return $this->pellets[$point->x()][$point->y()];
    }

    /**
     * observe and update game info
     */
    public function update()
    {
        $this->attachPellets();
        $this->cleanupPelletsUnderPacs();
        $this->cleanupPelletsIDontSee();
        $this->detachPellets();
    }

    public function attachPellets()
    {
        /** @var \Pellet $pellet */
        foreach (Helper::flatten($this->tick->visiblePellets()) as $pellet) {
            $this->cachePellets->attach($pellet);
            if ($pellet->isSuper()) {
                $this->cacheSupers->attach($pellet);
            }
        }
    }

    public function detachPellets()
    {
        $delete = new SplObjectStorage;
        /** @var \Pellet $pellet */
        foreach ($this->cachePellets as $pellet) {
            if ($pellet->isEaten()) {
                $delete->attach($pellet);
            }
        }
        $this->cachePellets->removeAll($delete);
        $this->cacheSupers->removeAll($delete);
    }

    public function possiblePellets(): SplObjectStorage
    {
        return $this->cachePellets;
    }

    public function superPellets(): SplObjectStorage
    {
        return $this->cacheSupers;
    }

    /**
     * clean all where i see pacs first
     */
    public function cleanupPelletsUnderPacs()
    {
        /** @var \Pac $pac */
        foreach (Helper::flatten($this->tick->visiblePacs()) as $pac) {
            if (!$this->isPelletKnown($pac->pos())) {
                continue;
            }
            $this->pellet($pac->pos())->eaten();
        }
    }

    /**
     * delete invisible pellets in pacs visible range
     */
    public function cleanupPelletsIDontSee()
    {
        /** @var \Pac $pac */
        foreach ($this->tick->visiblePacs(Pac::MINE) as $pac) {
            $paths = $this->field->paths($pac->pos());
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

    public function commands(): array
    {
        $result = [];
        /** @var \Pac $pac */
        foreach ($this->tick->visiblePacs(Pac::MINE) as $pac) {
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

    private $visiblePacsCount = 0;
    private $visiblePacs = [];

    private $visiblePelletsCount = 0;
    private $visiblePellets = [];

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function appendPac(Pac $pac): Tick
    {
        $this->visiblePacs[(int)$pac->isMine()][$pac->id()] = $pac;
        $this->visiblePacsCount++;
        return $this;
    }

    public function isPacVisible(int $im, int $id): bool
    {
        return isset($this->visiblePacs[$im][$id]);
    }

    public function visiblePac(int $im, int $id): Pac
    {
        if (!isset($this->visiblePacs[$im][$id])) {
            throw new \InvalidArgumentException("Pac {$im}.{$id} not visible");
        }
        return $this->visiblePacs[$im][$id];
    }

    public function visiblePacs(int $im = null): array
    {
        if ($im !== null) {
            return $this->visiblePacs[$im] ?? [];
        }
        return $this->visiblePacs;
    }

    public function appendPellet(Pellet $pellet): Tick
    {
        $this->visiblePellets[$pellet->x()][$pellet->y()] = $pellet;
        $this->visiblePelletsCount++;
        return $this;
    }

    public function isPelletVisible(Point $point): bool
    {
        return isset($this->visiblePellets[$point->x()][$point->y()]);
    }

    public function visiblePellet(Point $point): Pellet
    {
        if (!isset($this->visiblePellets[$point->x()][$point->y()])) {
            throw new \InvalidArgumentException("Pellet {$point->x()}.{$point->y()} not visible");
        }
        return $this->visiblePellets[$point->x()][$point->y()];
    }

    public function visiblePellets(): array
    {
        return $this->visiblePellets;
    }
}

abstract class AbstractStrategy
{
    /** @var \SplObjectStorage */
    protected $pacs;
    /** @var \Game */
    protected $game;

    public function __construct(SplObjectStorage $pacs, Game $game)
    {
        $this->pacs = $pacs;
        $this->game = $game;
    }

    abstract public function exec();

    protected function assign(Pac $pac, AbstractOrder $order)
    {
        if ($this->pacs->offsetGet($pac) instanceof AbstractOrder) {
            throw new \InvalidArgumentException('Trying to assign an order twice a tick');
        }
        $pac->assignOrder($order);
        $this->pacs->offsetSet($pac, $order);
    }
}

class NoopStrategy extends AbstractStrategy
{
    public function exec()
    {
        /** @var \Pac $pac */
        foreach ($this->pacs as $pac) {
            $this->assign($pac, new NoopOrder);
            debug("Pac {$pac->id()} has no orders, assigned noop fallback");
        }
    }
}

class CloseEnemyStrategy extends AbstractStrategy
{
    /** @var \SplObjectStorage */
    public $visibleEnemies;

    public function exec()
    {
        $this->visibleEnemies = new SplObjectStorage;
        /** @var \Pac $pac */
        foreach ($this->game->tick()->visiblePacs(Pac::ENEMY) as $pac) {
            $this->visibleEnemies->attach($pac->pos(), $pac);
        }

        /** @var \Pac $pac */
        foreach ($this->pacs as $pac) {
            $neighbours = $this->neighbours($pac);
            if (count($neighbours) !== 1) {
                continue;
            }
            if ($order = $this->react($pac, array_pop($neighbours))) {
                $this->assign($pac, $order);
                continue;
            }
        }
    }

    private function neighbours(Pac $pac): array
    {
        $adjacent = $this->game->field()->adjacent($pac->pos());
        $neighbours = [];
        foreach ($adjacent as $point) {
            if ($this->visibleEnemies->contains($point)) {
                $neighbours[] = $this->visibleEnemies->offsetGet($point);
            }
        }
        return $neighbours;
    }

    private function react(Pac $mine, Pac $enemy): ?AbstractOrder
    {
        $cmp = $mine->compare($enemy);
        if ($cmp === 1) {
            debug("Pac {$mine->id()} is stronger than {$enemy->id()}, decided to wait");
            return new NoopOrder;
        }
        if ($cmp === -1 && $mine->isPower()) {
            debug("Pac {$mine->id()} is weaker than {$enemy->id()}, decided to switch");
            return new SwithOrder(Pac::stronger($enemy->type()));
        }
        return null;
    }
}

class RushSupersStrategy extends AbstractStrategy
{
    public function exec()
    {
        $supers = $this->game->superPellets();
        $supersCount = $supers->count();
        if (!$supersCount) {
            return;
        }

        $pairs = [];
        /** @var \Pellet $pellet */
        foreach ($supers as $pellet) {
            /** @var \Pac $pac */
            foreach ($this->pacs as $pac) {
                $pairs[$pac->pos()->distance($pellet)][] = [$pac, $pellet];
            }
        }
        ksort($pairs);

        $assignedPacs = new SplObjectStorage;
        $assignedPellets = new SplObjectStorage;
        $pacsCount = count($this->pacs);
        foreach ($pairs as $solutions) {
            foreach ($solutions as $pair) {
                if ($assignedPacs->contains($pair[0]) || $assignedPellets->contains($pair[1])) {
                    continue;
                }
                $assignedPacs->offsetSet($pair[0], $pair[1]);
                $assignedPellets->attach($pair[1]);
            }
            if ($assignedPacs->count() === $pacsCount || $assignedPellets->count() === $supersCount) {
                break;
            }
        }

        foreach ($this->pacs as $pac) {
            if (!$assignedPacs->contains($pac)) {
                continue;
            }
            if ($order = $this->react($pac, $assignedPacs->offsetGet($pac))) {
                $this->assign($pac, $order);
                continue;
            }
        }
    }

    private function react(Pac $mine, Pellet $pellet): ?AbstractOrder
    {
        $distance = $mine->pos()->distance($pellet);
        debug("Pac {$mine->id()} choose nearest super {$pellet->x()}.{$pellet->y()}, distance {$distance}");
        return new MoveOrder($pellet);
    }
}

class PriorityVectorStrategy extends AbstractStrategy
{
    public function exec()
    {
        /** @var \Pac $pac */
        foreach ($this->pacs as $pac) {
            if ($order = $this->react($pac)) {
                $this->assign($pac, $order);
                continue;
            }
        }
    }

    private function react(Pac $mine): ?AbstractOrder
    {
        $paths = $this->game->field()->paths($mine->pos());
        $priority = [];
        foreach ($paths as $direction => $vector) {
            $priority[$direction] = $this->priority($direction, $vector);
        }
        arsort($priority);

        $best = key($priority);
        if ($priority[$best] < 1) {
            return null;
        }

        /** @var \SplDoublyLinkedList $vector */
        $vector = $paths[$best];
        // stop on cross
        foreach ($vector as $point) {
            $adjacent = $this->game->field()->adjacent($point);
            if (count($adjacent) > 2) {
                break;
            }
        }

        debug("Pac {$mine->id()} choosen vector to {$point->x()}.{$point->y()} with score {$priority[$best]}");

        return new MoveOrder($point);
    }

    private function priority(int $direction, SplDoublyLinkedList $vector): int
    {
        $weight = 0;
        $steps = $vector->count();
        /** @var \Point $point */
        foreach ($vector as $point) {
            if ($this->game->isPelletKnown($point)) {
                $pellet = $this->game->pellet($point);
                if ($pellet->isExists()) {
                    $weight += $pellet->isSuper() ? 10 : 1;
                } else {
                    $weight += -1 * $steps;
                }
            }
            $adjacent = $this->game->field()->adjacent($point);
            if (count($adjacent) > 2) {
                break;
            }
        }
        return $weight;
    }
}

class Box
{
    /** @var \SplObjectStorage */
    private $pacs;
    /** @var \AbstractStrategy */
    private $strategies;

    public function __construct(Game $game, array $strategies = null)
    {
        $this->pacs = new SplObjectStorage;
        foreach ($game->tick()->visiblePacs(Pac::MINE) as $pac) {
            $this->pacs->attach($pac);
        }

        $this->strategies = new SplQueue;
        if ($strategies === null) {
            $strategies = [
                CloseEnemyStrategy::class,
                RushSupersStrategy::class,
                PriorityVectorStrategy::class,
                NoopStrategy::class,
            ];
        }
        foreach ($strategies as $strategyClass) {
            $this->strategies->enqueue(new $strategyClass($this->pacs, $game));
        }
    }

    public function countFreePacs(): int
    {
        return $this->pacs->count();
    }

    public function exec()
    {
        /** @var \AbstractStrategy $strategy */
        foreach ($this->strategies as $strategy) {
            if (!$this->pacs->count()) {
                return;
            }
            $strategy->exec();
            $this->detach();
        }
    }

    public function detach()
    {
        $delete = new SplObjectStorage;
        foreach ($this->pacs as $pac) {
            if ($this->pacs->getInfo() !== null) {
                $delete->attach($pac);
            }
        }
        $this->pacs->removeAll($delete);
    }

    public function runToSuper()
    {
        if (!count($this->pacs)) {
            return;
        }

        // supers
        /** @var \Floor $pellet */
        $gold = [];
        foreach (Helper::flatten($this->pellets) as $pellet) {
            if ($pellet->isSuper()) {
                $gold[$pellet->compositeKey()] = $pellet;
            }
        }
        // no more gold?
        if (!count($gold)) {
            return;
        }

        $distances = [];
        /** @var \Pac $pac */
        foreach ($this->pacs as $pac) {
            foreach ($gold as $pellet) {
                $distances[] = [
                    'distance' => $pac->pos->distance($pellet),
                    'pacId' => $pac->id,
                    'goldId' => $pellet->compositeKey(),
                ];
            }
        }
        usort($distances, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // assign
        foreach ($distances as $distance) {
            // assigned
            if (!isset($this->pacs[$distance['pacId']]) || !isset($gold[$distance['goldId']])) {
                continue;
            }

            $order = new Order($gold[$distance['goldId']]);
            unset($gold[$distance['goldId']]);

            /** @var \Pac $pac */
            $pac = $this->pacs[$distance['pacId']];
            $pac->order = $order;
            unset($this->pacs[$distance['pacId']]);

            if (!count($gold) || !count($this->pacs)) {
                return;
            }
        }
    }

    public function agressive()
    {
        // any enemies?
        if (!count($this->field->visiblePacs[0])) {
            return;
        }

        // react close
        /** @var \Pac $enemy */
        foreach ($this->field->visiblePacs[0] as $enemy) {
            /** @var \Pac $mine */
            foreach ($this->field->visiblePacs[1] as $mine) {
                $distance = $mine->pos->distance($enemy->pos);
                if ($distance > 2) {
                    continue;
                }

                // can beat
                if ($mine->compare($enemy) === 1) {
                    if (!$mine->cooldown) {
                        $mine->order = null;
                        $mine->power = Pac::POWER_SPEED;
                        break;
                    }

                    $vector = $this->field->vector($mine->pos, $mine->pos->direction($enemy->pos));
                    $point = array_pop($vector);
                    $mine->order = new Order($point->x, $point->y);
                    debug("{$mine->id} close to {$enemy->id}, agressive!");

                    break;
                }
            }
        }
    }
}

if (defined('APP_TEST')) {
    return;
}

/**
 * Parsing game field
 */
$w = $h = 0;
fscanf(STDIN, "%d %d", $w, $h);
$raw = [];
for ($i = 0; $i < $h; $i++) {
    $raw[] = stream_get_line(STDIN, $w + 1, "\n");
}
$field = Field::factory($raw);
unset($w, $h, $raw);

/**
 * Init input variables
 */
$visiblePacCount = 0;
$visiblePacs = [];
$visiblePelletCount = 0;
$visiblePellets = [];

/**
 * Start game loop
 */
$game = new Game($field);

while (true) {
    $score = fscanf(STDIN, "%d %d");
    $game->turn(...$score);

    fscanf(STDIN, "%d", $visiblePacCount);
    $visiblePacs = [];
    for ($i = 0; $i < $visiblePacCount; $i++) {
        $visiblePacs[] = stream_get_line(STDIN, 64, "\n");
    }
    $game->processPacs($visiblePacs);

    fscanf(STDIN, "%d", $visiblePelletCount);
    $visiblePellets = [];
    for ($i = 0; $i < $visiblePelletCount; $i++) {
        $visiblePellets[] = stream_get_line(STDIN, 64, "\n");
    }
    $game->processPellets($visiblePellets);

    $game->update();

    $box = new Box($game);
    $box->exec();

    $commands = $game->commands();
    echo implode(' | ', $commands) . "\n";
}
