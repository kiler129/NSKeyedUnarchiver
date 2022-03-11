<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSDate;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSDate
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait
 */
class NSDateTest extends TestCase
{
    private NSDate $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSDate();
    }

    public function provideValidCFDateTime()
    {
        return [
            'negative value w/o microseconds' => [
                -978307300,
                new \DateTimeImmutable('December 31, 1969 23:58:20.000000 GMT'),
            ],
            'zero value w/o microseconds' => [0, new \DateTimeImmutable('January 1, 2001 00:00:00.000000 GMT')],
            'positive value w/o microseconds' => [
                63216306,
                new \DateTimeImmutable('January 2, 2003 16:05:06.000000 GMT'),
            ],

            'negative value with microseconds' => [
                -978307300.123456,
                new \DateTimeImmutable('December 31, 1969 23:58:20.123456 GMT'),
            ],
            'zero value with microseconds' => [0.789012, new \DateTimeImmutable('January 1, 2001 00:00:00.789012 GMT')],
            'positive value with microseconds' => [
                63216306.000111,
                new \DateTimeImmutable('January 2, 2003 16:05:06.000111 GMT'),
            ],
            'positive value with microseconds w/o leading zeros' => [
                63216306.123,
                new \DateTimeImmutable('January 2, 2003 16:05:06.123 GMT'),
            ],
        ];
    }

    /**
     * @dataProvider provideValidCFDateTime
     */
    public function testUnserializesCoreFoundationDateTime(int|float $ts, \DateTimeInterface $expected)
    {
        $this->subjectUnderTest->__unserialize(['NS.time' => $ts]);
        $this->assertEquals($expected, $this->subjectUnderTest->dateTime);
    }

    public function provideInvalidCFDateTime()
    {
        return [
            'empty string' => [''],
            'null' => [null],
            'valid string time' => ['January 1, 2001 00:00:00.000000 GMT' ],
            'invalid timestamp' => ['123.456.789'],
        ];
    }

    /**
     * @dataProvider provideInvalidCFDateTime
     */
    public function testThrowsOnInvalidTimestamp(mixed $ts)
    {
        $dt = new \DateTimeImmutable('1/2/2003 00:00:00.0');
        $this->subjectUnderTest->dateTime = clone $dt;

        try {
            $this->subjectUnderTest->__unserialize(['NS.time' => $ts]);
        } catch (\Throwable $e) {
            //Ensure that the correct exception is thrown
            $this->assertInstanceOf(MalformedArchiveException::class, $e);
            //Ensure existing date time is not reset
            $this->assertEquals($dt, $this->subjectUnderTest->dateTime);
        }
    }

}
