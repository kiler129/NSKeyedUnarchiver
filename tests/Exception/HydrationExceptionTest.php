<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\Exception;

use NoFlash\NSKeyedUnarchiver\Exception\HydrationException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\Exception\HydrationException
 */
class HydrationExceptionTest extends TestCase
{
    public function testCreateMalformedReferenceReportsInvalidReferenceReason(): void
    {
        $suts = HydrationException::createMalformedReference('dummy reason');

        $this->assertMatchesRegularExpression('#reference is invalid.+dummy reason#', $suts->getMessage());
    }

    public function testCreateMalformedReferenceResolvesPatterns()
    {
        $suts = HydrationException::createMalformedReference('%s-%d', 'test', 123);

        $this->assertStringEndsWith('test-123', $suts->getMessage());
    }
}
