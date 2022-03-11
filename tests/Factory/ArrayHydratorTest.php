<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\Factory;

use NoFlash\NSKeyedUnarchiver\Factory\ArrayHydrator;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\Factory\ArrayHydrator
 * @covers \NoFlash\NSKeyedUnarchiver\Factory\AbstractObjectHydrator
 */
class ArrayHydratorTest extends TestCase
{
    private ArrayHydrator $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new ArrayHydrator();
    }

    public function provideDifferentObjects()
    {
        yield 'empty' => [
            'NSEmpty', ['NSEmpty'], []
        ];

        yield 'single property' => [
            'NSFoo', ['aaa'], ['foo' => 'bar']
        ];

        yield 'unknown class chain' => [
            'NSSwiftCustom', null, ['a' => 'b']
        ];

        yield 'multiple properties' => [
            'NSAaaa', ['bbb', 'ccc', 'ddd'], [
                'one' => 'foo',
                'NS.two' => 'bar',
                'three' => new \stdClass(),
                'four' => 123,
                'five' => ['aaa', 'bbb', 'ccc'],
                'six' => M_PI,
            ]
        ];
    }

    /**
     * @dataProvider provideDifferentObjects
     */
    public function testSupportsHydrationOfAnyObject(string $nativeClass, ?array $classChain, array $properties)
    {
        $this->assertTrue($this->subjectUnderTest->canHydrateObject($nativeClass, $classChain, $properties));
    }

    /**
     * @dataProvider provideDifferentObjects
     */
    public function testHydratesAnyObject(string $nativeClass, ?array $classChain, array $properties)
    {
        $out = $this->subjectUnderTest->hydrateObject($nativeClass, $classChain, $properties);

        $this->assertIsArray($out);
        $this->assertIsNotObject($out);
        $this->assertCount(\count($properties), $out);

        foreach ($properties as $prop => $val) {
            $this->assertArrayHasKey($prop, $out);
            $this->assertSame($properties[$prop], $out[$prop]);
        }
    }

    public function testAddsNativeClassInformation()
    {
        $this->subjectUnderTest = new ArrayHydrator('_NCN', '_NCC');

        $out = $this->subjectUnderTest->hydrateObject('foo', ['bar'], ['go' => 'brr']);
        $this->assertCount(3, $out);
        $this->assertArrayHasKey('_NCN', $out);
        $this->assertArrayHasKey('_NCC', $out);
        $this->assertSame('foo', $out['_NCN']);
        $this->assertSame(['bar'], $out['_NCC']);
    }
}
