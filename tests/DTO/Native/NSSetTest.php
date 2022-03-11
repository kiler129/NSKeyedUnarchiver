<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSArray;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSSet;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Exception\OverflowException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSSet
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait
 */
class NSSetTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected NSSet $subjectUnderTest;
    protected const EXPECTED_NATIVE_CLASS = 'NSSet';

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSSet();
    }

    public function testIsIterable()
    {
        $this->assertIsIterable($this->subjectUnderTest);
    }

    public function testSupportsAddingElements()
    {
        $this->subjectUnderTest->addItem('a');
        $this->subjectUnderTest->addItem('b');
        $this->subjectUnderTest->addItem('c');

        $this->assertCount(3, $this->subjectUnderTest);
        $this->assertSame(['a', 'b', 'c'], \iterator_to_array($this->subjectUnderTest));
        $this->assertSame(['a', 'b', 'c'], $this->subjectUnderTest->toArray());
    }

    public function testThrowsWhenAlreadyExistingElementIsAdded()
    {
        //Strict comparison is expected so the same class can be added twice
        $this->subjectUnderTest->addItem(new \stdClass());
        $this->subjectUnderTest->addItem(new \stdClass());

        $another = new \stdClass();
        $this->subjectUnderTest->addItem($another);

        $this->expectException(OverflowException::class);
        $this->subjectUnderTest->addItem($another);
    }

    public function testHydratesSet()
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

    public function testRejectsDuplicatedValuesInDehydratedData()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.objects' => ['a', 'b', 'a']]);
    }
}
