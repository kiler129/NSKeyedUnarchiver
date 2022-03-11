<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSDictionary;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSDictionary
 */
class NSDictionaryTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected NSDictionary $subjectUnderTest;
    protected const EXPECTED_NATIVE_CLASS = 'NSDictionary';

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSDictionary();
    }

    public function testIsArrayAccessible()
    {
        $this->assertTrue($this->subjectUnderTest instanceof \ArrayAccess);
    }

    public function testArraySupportsStoringStringKeyedElements()
    {
        $this->subjectUnderTest['x'] = 'a';
        $this->subjectUnderTest['y'] = 'b';
        $this->subjectUnderTest['z'] = 'c';

        $this->assertSame('a', $this->subjectUnderTest['x']);
        $this->assertSame('b', $this->subjectUnderTest['y']);
        $this->assertSame('c', $this->subjectUnderTest['z']);
    }

    public function testArraySupportsReplacingElements()
    {
        $this->subjectUnderTest['x'] = 'a';
        $this->subjectUnderTest['y'] = 'b';
        $this->subjectUnderTest['z'] = 'c';
        $this->subjectUnderTest['y'] = 'd';

        $this->assertSame('a', $this->subjectUnderTest['x']);
        $this->assertSame('d', $this->subjectUnderTest['y']);
        $this->assertSame('c', $this->subjectUnderTest['z']);
    }

    public function testArrayDoesNotAllowNumericOffsets()
    {
        $this->subjectUnderTest['x'] = 'a';

        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest[1] = 'a';
    }

    public function testArrayDoesNotAllowAutonumberedOffsets()
    {
        $this->subjectUnderTest['x'] = 'a';

        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest[] = 'd';
    }

    public function testArraySupportsElementExistanceChecking()
    {
        $this->subjectUnderTest['x'] = 'a';
        $this->subjectUnderTest['y'] = 'b';
        $this->subjectUnderTest['z'] = 'c';

        $this->assertArrayHasKey('x', $this->subjectUnderTest);
        $this->assertArrayHasKey('z', $this->subjectUnderTest);
        $this->assertArrayNotHasKey('a', $this->subjectUnderTest);
    }

    public function testArraySupportsCountingWithAddedElements()
    {
        $this->subjectUnderTest['x'] = 'a';
        $this->subjectUnderTest['y'] = 'b';
        $this->subjectUnderTest['z'] = 'c';

        $this->assertCount(3, $this->subjectUnderTest);
    }

    public function testArrayAllowsRemovalOfElement()
    {
        $this->subjectUnderTest['x'] = 'a';
        $this->subjectUnderTest['y'] = 'b';
        $this->subjectUnderTest['z'] = 'c';
        unset($this->subjectUnderTest['z']);
        unset($this->subjectUnderTest['x']);

        $this->assertCount(1, $this->subjectUnderTest);
        $this->assertArrayNotHasKey('x', $this->subjectUnderTest);
        $this->assertArrayNotHasKey('z', $this->subjectUnderTest);
    }

    public function testArrayAllowsRemovalOfNonExistingElements()
    {
        $this->subjectUnderTest['x'] = 'a';
        $this->subjectUnderTest['y'] = 'b';
        $this->subjectUnderTest['z'] = 'c';
        unset($this->subjectUnderTest['f']);
        unset($this->subjectUnderTest[123]);

        $this->assertCount(3, $this->subjectUnderTest);
    }

    public function testArrayIsIterable()
    {
        $this->subjectUnderTest['x'] = 'a';
        $this->subjectUnderTest['y'] = 'b';
        $this->subjectUnderTest['z'] = 'c';

        $this->assertIsIterable($this->subjectUnderTest);

        //Deliberately not using assertSame() - dictionary doesn't need to retain order of keys
        $out = \iterator_to_array($this->subjectUnderTest);
        $this->assertDictionarySame(['x' => 'a', 'y' => 'b', 'z' => 'c'], $out);
    }

    public function testHydratesEmptyList()
    {
        $this->subjectUnderTest->__unserialize(['NS.keys' => [], 'NS.objects' => []]);

        $this->assertCount(0, $this->subjectUnderTest);
        $this->assertSame([], \iterator_to_array($this->subjectUnderTest));
    }

    public function testHydratesDictionary()
    {
        $originalData = [
            'one' => 'foo',
            'two' => 'bar',
            'three' => new \stdClass(),
            'four' => 123,
            'five' => ['aaa', 'bbb', 'ccc'],
            'six' => M_PI
        ];

        $this->subjectUnderTest->__unserialize(
            ['NS.keys' => \array_keys($originalData), 'NS.objects' => \array_values($originalData)]
        );


        $this->assertCount(\count($originalData), $this->subjectUnderTest);

        //Deliberately not using assertSame() - dictionary doesn't need to retain order of keys
        $out = \iterator_to_array($this->subjectUnderTest);
        $this->assertDictionarySame($originalData, $out);
    }

    public function testRejectsEmptyDehydratedData()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize([]);
    }

    public function testRejectsInvalidKey()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.data' => []]);
    }

    public function testRejectsInvalidDehydratedKeysType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.keys' => 123, 'NS.objects' => []]);
    }

    public function testRejectsInvalidDehydratedValuesType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.keys' => [], 'NS.objects' => 'test']);
    }

    public function testRejectsExtraRootKeys()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.objects' => [], 'NS.keys' => [], 'NS.extra' => []]);
    }

    public function testRejectsExtraDataKeys()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.keys' => ['a', 'b'], 'NS.objects' => ['x']]);
    }

    public function testRejectsExtraDataValues()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.keys' => ['a'], 'NS.objects' => ['x', 'y']]);
    }

    public function testRejectsNumericKeysInDehydratedData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest->__unserialize(['NS.keys' => ['a', 0], 'NS.objects' => ['x', 'y']]);
    }
}
