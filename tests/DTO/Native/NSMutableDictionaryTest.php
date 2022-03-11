<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableDictionary;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableDictionary
 */
class NSMutableDictionaryTest extends NSDictionaryTest
{
    protected const EXPECTED_NATIVE_CLASS = 'NSMutableDictionary';

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSMutableDictionary();
    }
}
