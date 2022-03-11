<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSArray;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSArray
 */
class NSArrayTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected NSArray $subjectUnderTest;
    protected const EXPECTED_NATIVE_CLASS = 'NSArray';

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSArray();
    }

    public function testIsArrayAccessible()
    {
        $this->assertTrue($this->subjectUnderTest instanceof \ArrayAccess);
    }

    public function testArraySupportsStoringSequentialElements()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';

        $this->assertSame('a', $this->subjectUnderTest[0]);
        $this->assertSame('b', $this->subjectUnderTest[1]);
        $this->assertSame('c', $this->subjectUnderTest[2]);
    }

    public function testArraySupportsStoringManuallySequencedElements()
    {
        $this->subjectUnderTest[0] = 'a';
        $this->subjectUnderTest[1] = 'b';
        $this->subjectUnderTest[2] = 'c';

        $this->assertSame('a', $this->subjectUnderTest[0]);
        $this->assertSame('b', $this->subjectUnderTest[1]);
        $this->assertSame('c', $this->subjectUnderTest[2]);
    }

    public function testArraySupportsStoringMixedElements()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[1] = 'b';
        $this->subjectUnderTest[] = 'c';

        $this->assertSame('a', $this->subjectUnderTest[0]);
        $this->assertSame('b', $this->subjectUnderTest[1]);
        $this->assertSame('c', $this->subjectUnderTest[2]);
    }

    public function testArraySupportsReplacingElements()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';
        $this->subjectUnderTest[1] = 'd';

        $this->assertSame('a', $this->subjectUnderTest[0]);
        $this->assertSame('d', $this->subjectUnderTest[1]);
        $this->assertSame('c', $this->subjectUnderTest[2]);
    }

    public function testArrayDoesNotAllowNegativeOffsets()
    {
        $this->subjectUnderTest[] = 'a';

        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest[-1] = 'd';
    }

    public function testArrayDoesNotAllowNonConsecutiveElements()
    {
        $this->subjectUnderTest[] = 'a';

        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest[2] = 'd';
    }

    public function testArraySupportsElementExistanceChecking()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';

        $this->assertArrayHasKey(0, $this->subjectUnderTest);
        $this->assertArrayHasKey(1, $this->subjectUnderTest);
        $this->assertArrayNotHasKey(3, $this->subjectUnderTest);
    }

    public function testArraySupportsCountingWithAddedElements()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';

        $this->assertCount(3, $this->subjectUnderTest);
    }

    public function testArrayAllowsRemovalOfLastElement()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';
        unset($this->subjectUnderTest[2]);

        $this->assertCount(2, $this->subjectUnderTest);
        $this->assertArrayNotHasKey(2, $this->subjectUnderTest);
    }

    public function testArrayDoesNotAllowsRemovalOfMiddleElements()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';

        $this->expectException(InvalidArgumentException::class);
        unset($this->subjectUnderTest[1]);
    }

    public function testArrayAllowsRemovalOfNonExistingElements()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';
        unset($this->subjectUnderTest[42]);

        $this->assertCount(3, $this->subjectUnderTest);
    }

    public function testArrayIsIterable()
    {
        $this->subjectUnderTest[] = 'a';
        $this->subjectUnderTest[] = 'b';
        $this->subjectUnderTest[] = 'c';

        $this->assertIsIterable($this->subjectUnderTest);
        $this->assertSame(['a', 'b', 'c'], \iterator_to_array($this->subjectUnderTest));
    }

    public function testHydratesEmptyList()
    {
        $this->subjectUnderTest->__unserialize(['NS.objects' => []]);

        $this->assertCount(0, $this->subjectUnderTest);
        $this->assertSame([], \iterator_to_array($this->subjectUnderTest));
    }

    public function testHydratesPureList()
    {
        $originalData = [
            'foo',
            'bar',
            new \stdClass(),
            123,
            ['aaa', 'bbb', 'ccc'],
            M_PI
        ];

        $this->subjectUnderTest->__unserialize(['NS.objects' => $originalData]);

        $this->assertCount(\count($originalData), $this->subjectUnderTest);
        $this->assertSame($originalData, \iterator_to_array($this->subjectUnderTest));
    }

    public function testRejectsEmptyDehydratedData()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->__unserialize([]);
    }

    public function testRejectsInvalidKey()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->__unserialize(['NS.data' => []]);
    }

    public function testRejectsInvalidDehydratedType()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->__unserialize(['NS.data' => 123]);
    }

    public function testRejectsExtraKeys()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->__unserialize(['NS.objects' => [], 'NS.keys' => []]);
    }

    public function testRejectsNonconsecutiveKeysInDehydratedData()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.objects' => ['a', 2 => 'b']]);
    }
}
