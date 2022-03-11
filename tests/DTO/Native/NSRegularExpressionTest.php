<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSRegularExpression;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSRegularExpression
 */
class NSRegularExpressionTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected const EXPECTED_NATIVE_CLASS = 'NSRegularExpression';
    protected NSRegularExpression $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSRegularExpression();
    }

    public function testHasNullPatternByDefault()
    {
        $this->assertNull($this->subjectUnderTest->pattern);
    }

    public function testHasNoOptionsByDefault()
    {
        $this->assertSame(0, $this->subjectUnderTest->options);
    }

    public function testUnserializesNullPatternToNull()
    {
        $this->subjectUnderTest->__unserialize(['NSPattern' => null, 'NSOptions' => 0]);

        $this->assertNull($this->subjectUnderTest->pattern);
    }

    public function testUnserializesDehydratedNSRegularExpression()
    {
        $this->subjectUnderTest->__unserialize(['NSPattern' => 'foo bar not valid regex', 'NSOptions' => 1234]);

        $this->assertSame('foo bar not valid regex', $this->subjectUnderTest->pattern);
        $this->assertSame(1234, $this->subjectUnderTest->options);
    }

    public function testThrowsArchiveExceptionOnIncompatibleBaseType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->expectExceptionMessageMatches('/expected.+string or null/i');
        $this->subjectUnderTest->__unserialize(['NSPattern' => true, 'NSOptions' => 1234]);
    }

    public function testThrowsArchiveExceptionOnIncompatibleRelativeType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->expectExceptionMessageMatches('/expected.+integer/i');
        $this->subjectUnderTest->__unserialize(['NSPattern' => '', 'NSOptions' => 'foo']);
    }

    public function provideInvalidContainer()
    {
        return [
            'empty array' => [[]],
            'missing NSPattern' => [['NSOptions' => 0]],
            'missing NSOptions' => [['NSPattern' => 'a']],
            'extra keys' => [['NSPattern' => 'a', 'NSOptions' => 0, 'NSFoo' => 'bar']],
        ];
    }

    /**
     * @dataProvider provideInvalidContainer
     */
    public function testThrowsWhenContainerIsNotFormattedCorrectly(array $data)
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize($data);
    }
}
