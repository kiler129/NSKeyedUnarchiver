<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\Factory;

use HaydenPierce\ClassFinder\ClassFinder;
use NoFlash\NSKeyedUnarchiver\DTO\HydrationAwareInterface;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\DomainException;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\OverflowException;
use NoFlash\NSKeyedUnarchiver\Exception\RuntimeException;
use NoFlash\NSKeyedUnarchiver\Factory\ClassMapHydrator;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\Factory\ClassMapHydrator
 * @covers \NoFlash\NSKeyedUnarchiver\Factory\AbstractObjectHydrator
 */
class ClassMapHydratorTest extends TestCase
{
    private ClassMapHydrator $subjectUnderTest;
    
    private const STANDARD_CLASS_MAP = [
        'ClassMapHydratorTest_HydrationAware' => ClassMapHydratorTest_HydrationAware::class,
        'ClassMapHydratorTest_SelfHydration' => ClassMapHydratorTest_SelfHydration::class,
        'ClassMapHydratorTest_ExistingPublicProps' => ClassMapHydratorTest_ExistingPublicProps::class,
        'ClassMapHydratorTest_DynamicProps' => ClassMapHydratorTest_DynamicProps::class,
        'ClassMapHydratorTest_Setters' => ClassMapHydratorTest_Setters::class,
        'ClassMapHydratorTest_MagicSetters' => ClassMapHydratorTest_MagicSetters::class,
    ];

    public function setUp(): void
    {
        $this->subjectUnderTest = new ClassMapHydrator(null, self::STANDARD_CLASS_MAP);
    }

    public function provideClassMapSetter()
    {
        yield '__construct' => [fn(iterable $map) => new ClassMapHydrator(null, $map)];
        yield 'setClassmap' => [fn(iterable $map) => (new ClassMapHydrator())->setClassMap($map)];
    }

    /**
     * @doesNotPerformAssertions
     * @dataProvider provideClassMapSetter
     */
    public function testAcceptsEmptyClassMap(callable $setter)
    {
        $setter(new \EmptyIterator());
    }

    /**
     * @dataProvider provideClassMapSetter
     */
    public function testMappingSourceMustBeUnique(callable $setter)
    {
        $duplicateProvider = function() {
            yield 'NSStd' => \stdClass::class;
            yield 'NSFoo' => \Exception::class;
            yield 'NSStd' => \DateTime::class;
        };

        $this->expectException(OverflowException::class);
        $setter($duplicateProvider());
    }

    /**
     * @dataProvider provideClassMapSetter
     */
    public function testMappedTargetMustBeAnExistingClass(callable $setter)
    {
        $this->expectException(RuntimeException::class);
        $setter(['NSFoo' => '\NoFlash\NSKeyedUnarchiver\Tests\NonExistentClass']);
    }

    public function testSupportsHydrationOfObjectsFromClassmap()
    {
        $this->subjectUnderTest->setClassMap([]);
        $this->assertFalse($this->subjectUnderTest->canHydrateObject('NSFoo', [], []));

        $this->subjectUnderTest->setClassMap(['NSFoo' => \stdClass::class]);
        $this->assertTrue($this->subjectUnderTest->canHydrateObject('NSFoo', [], []));
        $this->assertFalse($this->subjectUnderTest->canHydrateObject('NSBar', [], []));
    }

    public function testHydrationFailsOnUnmappedClass()
    {
        $this->subjectUnderTest->setClassMap(['NSFoo' => \stdClass::class]);

        $this->subjectUnderTest->hydrateObject('NSFoo', [], []);

        $this->expectException(InvalidArgumentException::class);
        $this->subjectUnderTest->hydrateObject('NSBar', [], []);
    }

    public function testSupportsInternalHydrationFactory()
    {
        $native = 'ClassMapHydratorTest_HydrationAware';
        $chain = [$native, 'foo', 'bar'];

        $out = $this->subjectUnderTest->hydrateObject($native, $chain, []);

        $this->assertInstanceOf(self::STANDARD_CLASS_MAP['ClassMapHydratorTest_HydrationAware'], $out);
        $this->assertTrue($out->createFromHydration, 'object expected to be created via createForHydration()');
        $this->assertSame($native, $out->nativeClass);
        $this->assertSame($chain, $out->classChain);
    }

    public function testSupportsInternalHydrationFactoryWithUnknownChains()
    {
        $native = 'ClassMapHydratorTest_HydrationAware';
        $chain = null;

        $out = $this->subjectUnderTest->hydrateObject($native, $chain, []);

        $this->assertInstanceOf(self::STANDARD_CLASS_MAP['ClassMapHydratorTest_HydrationAware'], $out);
        $this->assertTrue($out->createFromHydration, 'object expected to be created via createForHydration()');
        $this->assertSame($native, $out->nativeClass);
        $this->assertNull($out->classChain);
    }

    public function testSupportsInternalHydration()
    {
        $someData = ['foo' => 'bar', 'baz' => 'bar', '123' => 456];

        $out = $this->subjectUnderTest->hydrateObject('ClassMapHydratorTest_SelfHydration', [], $someData);

        $this->assertInstanceOf(self::STANDARD_CLASS_MAP['ClassMapHydratorTest_SelfHydration'], $out);
        $this->assertSame($someData, $out->passedData);
    }

    public function testHydratesToExistingPublicProperties()
    {
        $data = ['one' => 'foo', 'two' => 'bar', 'three' => 'baz'];

        $out = $this->subjectUnderTest->hydrateObject('ClassMapHydratorTest_ExistingPublicProps', [], $data);

        $this->assertInstanceOf(self::STANDARD_CLASS_MAP['ClassMapHydratorTest_ExistingPublicProps'], $out);
        $this->assertSame($data['one'], $out->one);
        $this->assertSame($data['two'], $out->two);
        $this->assertSame($data['three'], $out->three);
        $this->assertCount(3, \get_object_vars($out), 'dynamic properties are not expected');
    }

    public function testThrowsExceptionOnDynamicPropertiesCreation()
    {
        $this->expectException(DomainException::class);
        $this->subjectUnderTest->hydrateObject('ClassMapHydratorTest_DynamicProps', [], ['a' => 'b']);
    }

    public function testHydratesToSetters()
    {
        $out = $this->subjectUnderTest->hydrateObject('ClassMapHydratorTest_Setters', [], ['foo' => 'brrr']);

        $this->assertInstanceOf(self::STANDARD_CLASS_MAP['ClassMapHydratorTest_Setters'], $out);
        $this->assertTrue($out->usedSetter);
        $this->assertSame('brrr', $out->val);
    }

    public function testHydratesToMagicSet()
    {
        $out = $this->subjectUnderTest->hydrateObject('ClassMapHydratorTest_MagicSetters', [], ['foo' => 'brrr']);

        $this->assertInstanceOf(self::STANDARD_CLASS_MAP['ClassMapHydratorTest_MagicSetters'], $out);
        $this->assertTrue($out->usedSetter);
        $this->assertSame('foo', $out->var);
        $this->assertSame('brrr', $out->val);
    }

    /**
     * Extracting all classes from that namespace ensures that if a new one is added it will fail the test for it in
     * case someone forgot
     */
    public function provideNativeObjectsProvider()
    {
        $defaultData = [
            'NSArray' => ['NS.objects' => []],
            'NSDate' => ['NS.time' => 0],
            'NSMutableArray' => ['NS.objects' => []],
            'NSDictionary' => ['NS.keys' => [], 'NS.objects' => []],
            'NSMutableDictionary' => ['NS.keys' => [], 'NS.objects' => []],
            'NSRegularExpression' => ['NSPattern' => '', 'NSOptions' => 0],
            'NSSet' => ['NS.objects' => []],
            'NSUUID' => ['NS.uuidbytes' => "\xd9\xe7\xa1\x84\x5d\x5b\x11\xea\xa6\x2a\x34\x99\x71\x00\x62\xd0"]
        ];

        /** @var class-string $fqcn */
        foreach (ClassFinder::getClassesInNamespace('NoFlash\NSKeyedUnarchiver\DTO\Native') as $fqcn) {
            if ((new \ReflectionClass($fqcn))->isAbstract()) {
                continue;
            }

            $native = \constant($fqcn . '::NATIVE_CLASS');
            yield $native => [$native, $fqcn, $defaultData[$native] ?? []];
        }
    }

    /**
     * This test is porcelain... but it's too good
     *
     * @dataProvider provideNativeObjectsProvider
     */
    public function testHydratesStandardNativeTypes(string $native, string $mapped, array $data)
    {
        $this->subjectUnderTest = ClassMapHydrator::createWithNativeTypes();

        $this->assertTrue($this->subjectUnderTest->canHydrateObject($native, [], $data));
        $this->assertInstanceOf($mapped, $this->subjectUnderTest->hydrateObject($native, [], $data));
    }
}

class ClassMapHydratorTest_HydrationAware implements HydrationAwareInterface {
    public bool $createFromHydration = false;
    public string $nativeClass;
    public ?array $classChain;

    public static function createForHydration(string $nativeClass, ?array $classChain): static
    {
        $obj = new static();
        $obj->createFromHydration = true;
        $obj->nativeClass = $nativeClass;
        $obj->classChain = $classChain;

        return $obj;
    }
}

class ClassMapHydratorTest_SelfHydration implements SelfHydratableInterface {
    public array $passedData;

    public function __unserialize(array $data): void
    {
        $this->passedData = $data;
    }
}

class ClassMapHydratorTest_ExistingPublicProps {
    public string $one;
    public string $two;
    public string $three;
}

class ClassMapHydratorTest_DynamicProps {
}

class ClassMapHydratorTest_Setters {
    public bool $usedSetter = false;
    public string $val;
    public function setFoo(string $var): void {
        $this->usedSetter = true;
        $this->val = $var;
    }
}

class ClassMapHydratorTest_MagicSetters {
    public bool $usedSetter = false;
    public string $var;
    public string $val;
    public function __set(string $var, string $val) {
        $this->usedSetter = true;
        $this->var = $var;
        $this->val = $val;
    }
}
