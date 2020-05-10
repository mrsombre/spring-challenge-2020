<?php
declare(strict_types=1);

function debug($var)
{
    error_log(var_export($var, true));
}

class Helper
{
    public static function iterateFlatten(array $array)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                yield from self::iterateFlatten($v);
            } else {
                yield $v;
            }
        }
    }
}

class Point
{
    public const TOP = 'top';
    public const BOTTOM = 'bottom';
    public const LEFT = 'left';
    public const RIGHT = 'right';

    public $x;
    public $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function distance(Point $point): int
    {
        return abs($this->x - $point->x) + abs($this->y - $point->y);
    }

    public function composite(): string
    {
        return "{$this->x}.{$this->y}";
    }
}

abstract class Tile extends Point
{
    public const TYPE_WALL = 0;
    public const TYPE_FLOOR = 1;

    public $type;

    public static function factory(int $x, int $y, string $type): Tile
    {
        if ($type === ' ') {
            return new Pellet($x, $y);
        }
        return new Wall($x, $y);
    }

    public function isFloor(): bool
    {
        return $this->type === self::TYPE_FLOOR;
    }

    public function isWall(): bool
    {
        return $this->type === self::TYPE_WALL;
    }
}

class Wall extends Tile
{
    public $type = self::TYPE_WALL;
}

class Pellet extends Tile
{
    public const STATUS_EXISTS = 1;
    public const STATUS_GONE = 0;

    public $type = self::TYPE_FLOOR;
    public $status = self::STATUS_EXISTS;
    public $cost = 0;

    public function isExists(): bool
    {
        return $this->status === self::STATUS_EXISTS;
    }

    public function isSuper(): bool
    {
        return $this->cost === 10;
    }
}

class Order
{
    public const PELLET = 1;
    public const POINT = 0;

    /** @var \Pellet */
    public $pos;
    public $type;

    public function __construct(Pellet $pos, int $type = self::PELLET)
    {
        $this->pos = $pos;
        $this->type = $type;
    }

    public function isPellet(): bool
    {
        return $this->type === self::PELLET;
    }
}

class Pac
{
    public $id;
    public $isMine;

    public $type;
    public $speed;
    public $cooldown;

    /** @var \Pellet */
    public $pos;
    /** @var \Pellet */
    public $lastPos;

    /** @var \Order */
    public $order;

    public function __construct(int $id, bool $isMine)
    {
        $this->id = $id;
        $this->isMine = $isMine;
    }

    public function updatePosition(Pellet $position): Pac
    {
        $this->lastPos = $this->pos;
        $this->pos = $position;
        return $this;
    }

    public function updateOrder(Order $order): Pac
    {
        $this->order = $order;
        return $this;
    }

    public function isStuck(): bool
    {
        return $this->pos === $this->lastPos;
    }

    public function inPlace(): bool
    {
        return $this->order && $this->order->pos === $this->pos;
    }

    public function isMoving(): bool
    {
        if (!$this->order || $this->inPlace() || $this->isStuck()) {
            return false;
        }
        return true;
    }
}

class Field
{
    public $w = 0;
    public $h = 0;

    public $tiles = [];
    public $pacs = [];
    public $visiblePacs = [];
    public $visiblePellets = [];

    public static function factory(array $raw): Field
    {
        $w = strlen($raw[0]);
        $h = count($raw);
        $tiles = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $tiles[] = Tile::factory($x, $y, $raw[$y][$x]);
            }
        }

        return new static($tiles);
    }

    public function __construct(array $tiles)
    {
        /** @var \Tile $tile */
        foreach ($tiles as $tile) {
            if (!$tile instanceof \Tile) {
                $type = get_class($tile);
                throw new \InvalidArgumentException("Invalid tile type {$type}");
            }
            $this->tiles[$tile->x][$tile->y] = $tile;
            if ($tile->isFloor()) {
                $this->pellets[$tile->x][$tile->y] = $tile;
            }
        }

        $this->w = count($this->tiles);
        $this->h = count(current($this->tiles));
    }

    public function tile(int $x, int $y): Tile
    {
        if (!isset($this->tiles[$x][$y])) {
            throw new \InvalidArgumentException("Tile {$x}.{$y} not found");
        }
        return $this->tiles[$x][$y];
    }

    public function pellet(int $x, int $y): Pellet
    {
        if (!isset($this->tiles[$x][$y]) || !$this->tiles[$x][$y] instanceof Pellet) {
            throw new \InvalidArgumentException("Pellet {$x}.{$y} not found");
        }
        return $this->tiles[$x][$y];
    }

    public function pac(int $im, int $id): Pac
    {
        if (!isset($this->pacs[$im][$id])) {
            throw new \RuntimeException("Pac {$im}.{$id} not found");
        }
        return $this->pacs[$im][$id];
    }

    public function cleanVisiblePacs(): Field
    {
        $this->visiblePacs = [];
        return $this;
    }

    public function processPac(int $id, int $im, int $x, int $y, string $ipt, int $ips, int $ipc): Field
    {
        if (!isset($this->pacs[$im][$id])) {
            $this->pacs[$im][$id] = new Pac($id, (bool)$im);
        }

        $pac = $this->pac($im, $id);
        $this->visiblePacs[$x][$y] = $pac;
        $pac->updatePosition($this->pellet($x, $y));
        return $this;
    }

    public function cleanVisiblePellets(): Field
    {
        $this->visiblePellets = [];
        return $this;
    }

    public function processPellet(int $x, int $y, int $cost): Field
    {
        $pellet = $this->pellet($x, $y);
        $pellet->status = Pellet::STATUS_EXISTS;
        $pellet->cost = $cost;
        $this->visiblePellets[$x][$y] = $pellet;
        return $this;
    }
}

class Game
{
    public $tick = 0;
    public $myScore = 0;
    public $opponentScore = 0;
}

class Box
{
    /** @var \Field */
    public $field;
    /** @var \Game */
    public $game;

    public $pacs = [];
    public $pellets = [];

    public function __construct(Field $field, Game $game)
    {
        $this->field = $field;
        $this->game = $game;

        // all active pellets
        /** @var \Tile $tile */
        foreach (Helper::iterateFlatten($field->tiles) as $tile) {
            if ($tile instanceof Pellet) {
                $this->pellets[$tile->x][$tile->y] = $tile;
            }
        }
    }

    public function run()
    {
        $this->update();

        // gold rush
        $this->runToSuper();
        // go nearest
        $this->goToNearest();
    }

    public function update()
    {
        $this->cleanup();
    }

    public function cleanup()
    {
        /** @var \Pellet $pellet */
        // set all pellets gone which i can't see
        // i'll change this later
        foreach (Helper::iterateFlatten($this->pellets) as $pellet) {
            if (!isset($this->field->visiblePellets[$pellet->x][$pellet->y])) {
                $pellet->status = Pellet::STATUS_GONE;
            }
        }

        // delete all tracking pellets which are gone
        $delete = [];
        foreach (Helper::iterateFlatten($this->pellets) as $pellet) {
            if (!$pellet->isExists()) {
                $delete[] = [$pellet->x, $pellet->y];
            }
        }
        foreach ($delete as $pos) {
            unset($this->pellets[$pos[0]][$pos[1]]);
            if (!count($this->pellets[$pos[0]])) {
                unset($this->pellets[$pos[0]]);
            }
        }

        // cleanup orders
        /** @var \Pac $pac */
        foreach ($this->field->pacs[1] as $pac) {
            // remove orders for pacs in place
            if ($pac->inPlace()) {
                $pac->order = null;
                continue;
            }
            // remove if pellet is gone
            if ($pac->order && $pac->order->isPellet() && !isset($this->pellets[$pac->order->pos->x][$pac->order->pos->y])) {
                $pac->order = null;
                continue;
            }
        }

        $this->pacs = [];
        /** @var \Pac $pac */
        foreach ($this->field->pacs[1] as $pac) {
            if (!$pac->isMoving()) {
                $this->pacs[$pac->id] = $pac;
                continue;
            }
        }
    }

    public function runToSuper()
    {
        if (!count($this->pacs)) {
            return;
        }

        // supers
        /** @var \Pellet $pellet */
        $gold = [];
        foreach (Helper::iterateFlatten($this->pellets) as $pellet) {
            if ($pellet->isSuper()) {
                $gold[$pellet->composite()] = $pellet;
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
                    'goldId' => $pellet->composite(),
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

    public function goToNearest()
    {
        /** @var \Pac $pac */
        foreach ($this->pacs as $pac) {
            $x = $pac->pos->x;
            $m = $x <= ($this->field->w / 2) ? Point::LEFT : Point::RIGHT;
            $this->findNearest($pac, $m);
        }
    }

    /// SHITCODE MIDNIGHT CODED! DO NOT USE IN PRODUCTION!!
    private function findNearest(Pac $pac, string $m): bool
    {
        $x = $pac->pos->x;
        if ($m === Point::LEFT) {
            while ($x >= 0) {
                $pos = $this->findNearestInCol($pac, $x);
                if (!$pos) {
                    $x--;
                } else {
                    $order = new Order($pos);
                    $pac->order = $order;
                    return true;
                }
            }
            return $this->findNearest($pac, Point::RIGHT);
        } else {
            while ($x < $this->field->w) {
                $pos = $this->findNearestInCol($pac, $x);
                if (!$pos) {
                    $x++;
                } else {
                    $order = new Order($pos);
                    $pac->order = $order;
                    return true;
                }
            }
            return $this->findNearest($pac, Point::LEFT);
        }
    }

    private function findNearestInCol(Pac $pac, int $x): ?Pellet
    {
        if (!isset($this->pellets[$x])) {
            return null;
        }

        $distances = [];
        /** @var \Pellet $pellet */
        foreach ($this->pellets[$x] as $y => $pellet) {
            $distances[] = [
                'distance' => $pac->pos->distance($pellet),
                'pelletId' => $y,
            ];
        }
        usort($distances, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        $nearest = current($distances);

        return $this->pellets[$x][$nearest['pelletId']];
    }
}

if (defined('TEST') && TEST) {
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
$visiblePacCount = $visiblePelletCount = 0;
$ix = $iy = 0;
$id = $im = $ips = $ipc = 0;
$ipt = '';

/**
 * Start game loop
 */
$game = new Game;
$box = new Box($field, $game);

while (true) {
    $game->tick++;
    fscanf(STDIN, "%d %d", $game->myScore, $game->opponentScore);

    fscanf(STDIN, "%d", $visiblePacCount);
    $field->cleanVisiblePacs();
    for ($i = 0; $i < $visiblePacCount; $i++) {
        fscanf(
            STDIN,
            "%d %d %d %d %s %d %d",
            $id,
            $im,
            $ix,
            $iy,
            $ipt,
            $ips,
            $ipc
        );
        $field->processPac($id, $im, $ix, $iy, $ipt, $ips, $ipc);
    }

    fscanf(STDIN, "%d", $visiblePelletCount);
    $field->cleanVisiblePellets();
    for ($i = 0; $i < $visiblePelletCount; $i++) {
        fscanf(
            STDIN,
            "%d %d %d",
            $ix,
            $iy,
            $id
        );
        $field->processPellet($ix, $iy, $id);
    }

    $box->run();
    /** @var \Pac $pac */
    $lines = [];
    foreach ($field->pacs[1] as $pac) {
        if ($pac->order) {
            $lines[] = "MOVE {$pac->id} {$pac->order->pos->x} {$pac->order->pos->y}";
        }
    }
    echo implode(' | ', $lines) . "\n";
}
