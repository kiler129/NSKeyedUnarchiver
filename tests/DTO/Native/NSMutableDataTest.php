<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableData;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableData
 */
class NSMutableDataTest extends NSDataTest
{
    protected const EXPECTED_NATIVE_CLASS = 'NSMutableData';

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSMutableData();
    }
}
