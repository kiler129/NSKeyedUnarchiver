<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO;

use NoFlash\NSKeyedUnarchiver\DTO\NSIncompleteObject;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\NSIncompleteObject
 */
class NSIncompleteObjectTest extends TestCase
{
    protected NSIncompleteObject $subjectUnderTest;

    private const NATIVE_CLASS = 'NSSampleObject';
    private const NATIVE_CHAIN = [self::NATIVE_CLASS, 'a', 'b', 'c'];

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSIncompleteObject(self::NATIVE_CLASS, self::NATIVE_CHAIN);
    }

    public function testContainsNativeClassInfoFromConstructor()
    {
        $this->assertSame(self::NATIVE_CLASS, $this->subjectUnderTest->__NSNativeClass);
        $this->assertSame(self::NATIVE_CHAIN, $this->subjectUnderTest->__NSClassChain);
    }

    public function testContainsNativeClassInfoFromHydrationFactory()
    {
        $nativeClass = 'NSFoo';
        $nativeChain = ['x', 'y', 'z', 'aaa'];

        $this->subjectUnderTest = NSIncompleteObject::createForHydration($nativeClass, $nativeChain);
        $this->assertSame($nativeClass, $this->subjectUnderTest->__NSNativeClass);
        $this->assertSame($nativeChain, $this->subjectUnderTest->__NSClassChain);
    }

    public function testSupportsUnknownClassChainDuringConstruction()
    {
        $this->subjectUnderTest = new NSIncompleteObject(self::NATIVE_CLASS, null);
        $this->assertNull($this->subjectUnderTest->__NSClassChain);
    }

    public function testSupportsUnknownClassChainFromHydrationFactory()
    {
        $this->subjectUnderTest = NSIncompleteObject::createForHydration(self::NATIVE_CLASS, null);
        $this->assertNull($this->subjectUnderTest->__NSClassChain);
    }

    public function testAllowsDynamicProperties()
    {
        $this->subjectUnderTest->foo = 'bar';
        $this->subjectUnderTest->baz = 'boo';

        $this->assertObjectHasAttribute('foo', $this->subjectUnderTest);
        $this->assertObjectHasAttribute('baz', $this->subjectUnderTest);
        $this->assertSame('bar', $this->subjectUnderTest->foo);
        $this->assertSame('boo', $this->subjectUnderTest->baz);
    }

    public function testIsIterable()
    {
        $this->assertIsIterable($this->subjectUnderTest);
        $this->assertSame([], \iterator_to_array($this->subjectUnderTest));
    }

    public function provideDataRepresentation()
    {
        return [
            'iterator' => [fn($sut) => \iterator_to_array($sut)],
            'array casting' => [fn($sut) => $sut->toArray()],
        ];
    }

    /**
     * @dataProvider provideDataRepresentation
     */
    public function testDynamicPropertiesAreAccessible(callable $getData)
    {
        $this->subjectUnderTest->aaa = 'bbb';
        $this->subjectUnderTest->bbb = 'ccc';
        $this->subjectUnderTest->xyz = 'abc';

        $itRes = $getData($this->subjectUnderTest);
        $this->assertCount(3, $itRes);
        $this->assertArrayHasKey('aaa', $itRes);
        $this->assertArrayHasKey('bbb', $itRes);
        $this->assertArrayHasKey('xyz', $itRes);
        $this->assertSame('bbb', $itRes['aaa']);
        $this->assertSame('ccc', $itRes['bbb']);
        $this->assertSame('abc', $itRes['xyz']);
    }

    /**
     * @dataProvider provideDataRepresentation
     */
    public function testHydratesEmptyList(callable $getData)
    {
        $this->subjectUnderTest->__unserialize([]);

        $data = $getData($this->subjectUnderTest);
        $this->assertCount(0, $data);
        $this->assertSame([], $data);
    }

    /**
     * @dataProvider provideDataRepresentation
     */
    public function testHydratesProperties(callable $getData)
    {
        $originalData = [
            'one' => 'foo',
            'NS.two' => 'bar',
            'three' => new \stdClass(),
            'four' => 123,
            'five' => ['aaa', 'bbb', 'ccc'],
            'six' => M_PI,
            123 => 'abc',
        ];

        $this->subjectUnderTest->__unserialize($originalData);

        $this->assertCount(\count($originalData), $this->subjectUnderTest);
        //Deliberately not using assertSame() - dictionary doesn't need to retain order of keys
        $out = $getData($this->subjectUnderTest);
        $this->assertDictionarySame($originalData, $out);
    }

    public function provideExistingProperties()
    {
        $rcl = new \ReflectionClass(NSIncompleteObject::class);

        foreach ($rcl->getProperties() as $property) {
            //we cannot use getProperties() filter here to be future proof (filter doesn't allow "all but static")
            if (!$property->isStatic()) {
                yield [$property->getName()];
            }
        }
    }

    /**
     * @dataProvider provideExistingProperties
     */
    public function testHydrationDoesntAllowExistingPropertiesOverride(string $prop)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subjectUnderTest->__unserialize(['foo' => 'bar', $prop => 'aaa', 'bb' => 'ccc']);
    }

    public function testHydrationDoesntAllowExistingDynamicPropertiesOverride()
    {
        $this->subjectUnderTest->go = 'brrr';

        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest->__unserialize(['go' => 'aaa', 'bb' => 'ccc']);
    }
}
