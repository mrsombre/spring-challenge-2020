<?php
declare(strict_types=1);

class HelperTest extends \PHPUnit\Framework\TestCase
{
    public function testIterateFlatten()
    {
        $multiArray = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
        ];

        $item = 0;
        $i = 0;
        foreach (Helper::flatten($multiArray) as $item) {
            $i++;
        }
        self::assertSame(9, $i);
        self::assertSame(9, $item);
    }
}
