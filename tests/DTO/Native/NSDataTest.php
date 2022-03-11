<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSData;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSData
 */
class NSDataTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected const EXPECTED_NATIVE_CLASS = 'NSData';
    protected NSData $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSData();
    }

    public function testHasNullDataByDefault()
    {
        $this->assertNull($this->subjectUnderTest->data);
    }

    public function testIsStringable()
    {
        $this->subjectUnderTest->data = 'test123';

        $this->assertSame('test123', (string)$this->subjectUnderTest);
    }

    public function testCastsNullDataToEmptyString()
    {
        $this->subjectUnderTest->data = null;

        $this->assertSame('', (string)$this->subjectUnderTest);
    }

    public function testUnserializesEmptyDehydratedDataToNull()
    {
        $this->subjectUnderTest->__unserialize([]);

        $this->assertNull($this->subjectUnderTest->data);
    }

    public function testUnserializesNullDataToNull()
    {
        $this->subjectUnderTest->__unserialize(['NS.data' => null]);

        $this->assertNull($this->subjectUnderTest->data);
    }


    public function testUnserializesDehydratedNSData()
    {
        $this->subjectUnderTest->__unserialize(['NS.data' => 'foo bar']);

        $this->assertSame('foo bar', $this->subjectUnderTest->data);
    }

    public function testThrowsArchiveExceptionOnIncompatibleType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.data' => 123]);
    }
}
