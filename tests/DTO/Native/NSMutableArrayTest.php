<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableArray;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableArray
 */
class NSMutableArrayTest extends NSArrayTest
{
    protected const EXPECTED_NATIVE_CLASS = 'NSMutableArray';

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSMutableArray();
    }
}
