<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSURL;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSURL
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait
 */
class NSURLTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected const EXPECTED_NATIVE_CLASS = 'NSURL';
    protected NSURL $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSURL();
    }

    public function testHasNullBaseByDefault()
    {
        $this->assertNull($this->subjectUnderTest->base);
    }

    public function testHasNullRelativeByDefault()
    {
        $this->assertNull($this->subjectUnderTest->relative);
    }

    public function testUnserializesEmptyDehydratedDataToNulls()
    {
        $this->subjectUnderTest->__unserialize([]);

        $this->assertNull($this->subjectUnderTest->base);
        $this->assertNull($this->subjectUnderTest->relative);
    }

    public function testUnserializesNullBaseToNull()
    {
        $this->subjectUnderTest->__unserialize(['NS.base' => null]);

        $this->assertNull($this->subjectUnderTest->base);
    }

    public function testUnserializesNullRelativeToNull()
    {
        $this->subjectUnderTest->__unserialize(['NS.relative' => null]);

        $this->assertNull($this->subjectUnderTest->relative);
    }

    public function testUnserializesDehydratedNSData()
    {
        $this->subjectUnderTest->__unserialize(['NS.base' => 'https://example.com', 'NS.relative' => '/foo']);

        $this->assertSame('https://example.com', $this->subjectUnderTest->base);
        $this->assertSame('/foo', $this->subjectUnderTest->relative);
    }

    public function testThrowsArchiveExceptionOnIncompatibleBaseType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.base' => 123]);
    }

    public function testThrowsArchiveExceptionOnIncompatibleRelativeType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.relative' => 123]);
    }
}
