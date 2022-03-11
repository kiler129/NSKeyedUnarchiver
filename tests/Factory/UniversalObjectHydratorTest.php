<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\Factory;

use NoFlash\NSKeyedUnarchiver\DTO\HydrationAwareInterface;
use NoFlash\NSKeyedUnarchiver\DTO\NSIncompleteObject;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\DomainException;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Factory\UniversalObjectHydrator;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\Factory\UniversalObjectHydrator
 * @covers \NoFlash\NSKeyedUnarchiver\Factory\AbstractObjectHydrator
 */
class UniversalObjectHydratorTest extends TestCase
{
    private UniversalObjectHydrator $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new UniversalObjectHydrator();
    }


    public function provideDifferentNativeObjects()
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
     * @dataProvider provideDifferentNativeObjects
     */
    public function testSupportsHydrationOfAnyObject(string $nativeClass, ?array $classChain, array $properties)
    {
        $this->assertTrue($this->subjectUnderTest->canHydrateObject($nativeClass, $classChain, $properties));
    }

    public function testHydratesToIncompleteObjectByDefault()
    {
        $out = $this->subjectUnderTest->hydrateObject('', [], []);

        $this->assertInstanceOf(NSIncompleteObject::class, $out);
    }

    public function testHydratesToCustomObject()
    {
        $this->subjectUnderTest = new UniversalObjectHydrator(UniversalObjectHydratorTest_ExistingPublicProps::class);
        $out = $this->subjectUnderTest->hydrateObject('', [], []);

        $this->assertInstanceOf(UniversalObjectHydratorTest_ExistingPublicProps::class, $out);
    }

    public function testSupportsChangingHydrationTarget()
    {
        $this->subjectUnderTest->setTargetClass(UniversalObjectHydratorTest_DynamicProps::class);
        $out = $this->subjectUnderTest->hydrateObject('', [], []);

        $this->assertInstanceOf(UniversalObjectHydratorTest_DynamicProps::class, $out);
    }

    public function testNonExistantClassCannotBeSetAsDefaultHydrationTarget()
    {
        $this->expectException(InvalidArgumentException::class);
        new UniversalObjectHydrator('\NoFlash\NSKeyedUnarchiver\Tests\NonExistentClass');
    }

    public function testNonExistantClassCannotBeSetAsCustomHydrationTarget()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest->setTargetClass('\NoFlash\NSKeyedUnarchiver\Tests\NonExistentClass');
    }

    public function testSupportsInternalHydrationFactory()
    {
        $native = 'NSFoo';
        $chain = [$native, 'foo', 'bar'];
        $this->subjectUnderTest->setTargetClass(UniversalObjectHydratorTest_HydrationAware::class);

        /** @var UniversalObjectHydratorTest_HydrationAware $out */
        $out = $this->subjectUnderTest->hydrateObject($native, $chain, []);

        $this->assertTrue($out->createFromHydration, 'object expected to be created via createForHydration()');
        $this->assertSame($native, $out->nativeClass);
        $this->assertSame($chain, $out->classChain);
    }

    public function testSupportsInternalHydration()
    {
        $this->subjectUnderTest->setTargetClass(UniversalObjectHydratorTest_SelfHydration::class);

        $someData = ['foo' => 'bar', 'baz' => 'bar', '123' => 456];
        /** @var UniversalObjectHydratorTest_SelfHydration $out */
        $out = $this->subjectUnderTest->hydrateObject('', [], $someData);

        $this->assertSame($someData, $out->passedData);
    }

    public function testHydratesToExistingPublicProperties()
    {
        $data = ['one' => 'foo', 'two' => 'bar', 'three' => 'baz'];

        $this->subjectUnderTest->setTargetClass(UniversalObjectHydratorTest_ExistingPublicProps::class);
        $out = $this->subjectUnderTest->hydrateObject('', [], $data);

        $this->assertSame($data['one'], $out->one);
        $this->assertSame($data['two'], $out->two);
        $this->assertSame($data['three'], $out->three);
        $this->assertCount(3, \get_object_vars($out), 'dynamic properties are not expected');
    }

    public function testHydratesToDynamicPublicProperties()
    {
        $data = ['one' => 'foo', 'two' => 'bar', 'three' => 'baz'];

        $this->subjectUnderTest->setTargetClass(UniversalObjectHydratorTest_DynamicProps::class);
        $out = $this->subjectUnderTest->hydrateObject('', [], $data);

        $this->assertObjectHasAttribute('one', $out);
        $this->assertObjectHasAttribute('two', $out);
        $this->assertObjectHasAttribute('three', $out);
        $this->assertSame($data['one'], $out->one);
        $this->assertSame($data['two'], $out->two);
        $this->assertSame($data['three'], $out->three);
        $this->assertCount(3, \get_object_vars($out), 'invalid number of dynamic props added');
    }

    public function testThrowsExceptionOnDynamicPropertiesCreationWhenItsDisabled()
    {
        $this->subjectUnderTest = new UniversalObjectHydrator(UniversalObjectHydratorTest_DynamicProps::class, false);

        $this->expectException(DomainException::class);
        $this->subjectUnderTest->hydrateObject('NSFoo', [], ['a' => 'b']);
    }

    public function testHydratesToSetters()
    {
        $this->subjectUnderTest->setTargetClass(UniversalObjectHydratorTest_Setters::class);
        /** @var UniversalObjectHydratorTest_Setters $out */
        $out = $this->subjectUnderTest->hydrateObject('NSFoo', [], ['foo' => 'brrr']);

        $this->assertTrue($out->usedSetter);
        $this->assertSame('brrr', $out->val);
    }

    public function testHydratesToMagicSet()
    {
        $this->subjectUnderTest->setTargetClass(UniversalObjectHydratorTest_MagicSetters::class);
        /** @var UniversalObjectHydratorTest_MagicSetters $out */
        $out = $this->subjectUnderTest->hydrateObject('NSFoo', [], ['foo' => 'brrr']);

        $this->assertTrue($out->usedSetter);
        $this->assertSame('foo', $out->var);
        $this->assertSame('brrr', $out->val);
    }
}


class UniversalObjectHydratorTest_HydrationAware implements HydrationAwareInterface {
    public bool $createFromHydration = false;
    public string $nativeClass;
    public array $classChain;

    public static function createForHydration(string $nativeClass, ?array $classChain): static
    {
        $obj = new static();
        $obj->createFromHydration = true;
        $obj->nativeClass = $nativeClass;
        $obj->classChain = $classChain;

        return $obj;
    }
}

class UniversalObjectHydratorTest_SelfHydration implements SelfHydratableInterface {
    public array $passedData;

    public function __unserialize(array $data): void
    {
        $this->passedData = $data;
    }
}

class UniversalObjectHydratorTest_ExistingPublicProps {
    public string $one;
    public string $two;
    public string $three;
}

class UniversalObjectHydratorTest_DynamicProps {
}

class UniversalObjectHydratorTest_Setters {
    public bool $usedSetter = false;
    public string $val;
    public function setFoo(string $var): void {
        $this->usedSetter = true;
        $this->val = $var;
    }
}

class UniversalObjectHydratorTest_MagicSetters {
    public bool $usedSetter = false;
    public string $var;
    public string $val;
    public function __set(string $var, string $val) {
        $this->usedSetter = true;
        $this->var = $var;
        $this->val = $val;
    }
}
