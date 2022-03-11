<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

trait NativeClassRepresentationTrait
{
    public function testRepresentsProperNativeClass()
    {
        $this->assertSame(static::EXPECTED_NATIVE_CLASS, constant($this->subjectUnderTest::class . '::NATIVE_CLASS'));
    }
}
