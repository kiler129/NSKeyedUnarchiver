<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO;

use NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait
 */
class FlatNSUnserializerTraitTest extends TestCase
{
    private SelfHydratableInterface $subjectUnderTest;

    public function testUnserializesEmptyData()
    {
        $this->subjectUnderTest = new class() implements SelfHydratableInterface {
            use FlatNSUnserializerTrait;
            const NATIVE_CLASS = '';
        };
        $this->subjectUnderTest->__unserialize([]);

        $this->assertSame([], \get_object_vars($this->subjectUnderTest));
    }

    public function testUnserializesDehydratedNSObject()
    {
        $this->subjectUnderTest = new class() implements SelfHydratableInterface {
            use FlatNSUnserializerTrait;
            const NATIVE_CLASS = '';

            public $foo;
            public $baz;
            public $xxx;
        };
        $this->subjectUnderTest->__unserialize(['NS.foo' => 'bar', 'NS.baz' => 'booo', 'NS.xxx' => 123]);

        $this->assertSame('bar', $this->subjectUnderTest->foo);
        $this->assertSame('booo', $this->subjectUnderTest->baz);
        $this->assertSame(123, $this->subjectUnderTest->xxx);
    }

    public function testDoesNotAddDynamicProperties()
    {
        $this->subjectUnderTest = new class() implements SelfHydratableInterface {
            use FlatNSUnserializerTrait;
            const NATIVE_CLASS = '';

            public $foo;
        };

        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.foo' => 'bar', 'NS.bar' => 'aaa']);
    }

    public function testDoesNotAllowNonNSProperties()
    {
        $this->subjectUnderTest = new class() implements SelfHydratableInterface {
            use FlatNSUnserializerTrait;
            const NATIVE_CLASS = '';

            public $foo;
        };

        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['foo' => 'bar']);
    }

    public function testDoesNotAllowUnnamedProperties()
    {
        $this->subjectUnderTest = new class() implements SelfHydratableInterface {
            use FlatNSUnserializerTrait;
            const NATIVE_CLASS = '';

            public $foo;
        };

        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.' => 'bar']);
    }

    public function testThrowsArchiveExceptionOnIncompatibleType()
    {
        $this->subjectUnderTest = new class() implements SelfHydratableInterface {
            use FlatNSUnserializerTrait;
            const NATIVE_CLASS = '';

            public string $foo;
        };

        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.foo' => 123]);
    }
}
