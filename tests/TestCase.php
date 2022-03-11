<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function assertDictionarySame(array $expected, $actual, string $message = ''): void
    {
        \ksort($expected);
        \ksort($actual);

        $this->assertSame($expected, $actual, $message);
    }
}
