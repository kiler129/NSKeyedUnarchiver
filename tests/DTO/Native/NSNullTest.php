<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSNull;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSNull
 */
class NSNullTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected const EXPECTED_NATIVE_CLASS = 'NSNull';
    private NSNull $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSNull();
    }

    public function testThrowsWhenAnyDataIsPresentOnUnserialize()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize([null]);
    }
}
