<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\NSKeyedUnarchiverTest;

use NoFlash\NSKeyedUnarchiver\Factory\HydratorInterface;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\LogicException;
use NoFlash\NSKeyedUnarchiver\NSKeyedUnarchiver;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\NSKeyedUnarchiver
 *
 * This class tests small subset of functionality in as units, unlike other ones which are testing behaviors.
 *
 * @testdox NSKeyedUnarchiver - unit tests
 */
class NSKeyedUnarchiverTest_Unit extends AbstractNSKeyedUnarchiverTest
{
    private NSKeyedUnarchiver $subjectUnderTest;

    /**
     * @doesNotPerformAssertions
     */
    public function testAllowsEmptyHydratorsCollection()
    {
        new NSKeyedUnarchiver(new \EmptyIterator());
    }

    public function testThrowsWhenInvalidHydratorIsPassed()
    {
        $brokenHydrator = $this->createStub(\Iterator::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/hydrator #2/i');
        $this->expectExceptionMessageMatches('/' . \preg_quote($brokenHydrator::class, '/') . '/');
        new NSKeyedUnarchiver(
            [
                $this->createStub(HydratorInterface::class),
                $this->createStub(HydratorInterface::class),
                $brokenHydrator,
                $this->createStub(HydratorInterface::class),
            ]
        );
    }

    /**
     * @dataProvider provideArchivedDataDecoder
     */
    public function testUsesCustomHydrator(callable $unarchiveFn)
    {
        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->expects($this->atLeastOnce())
            ->method('canHydrateObject')
            ->with('TestDummy', null, ['foo' => 'bar'])
            ->willReturn(true);
        $hydrator->expects($this->atLeastOnce())
            ->method('hydrateObject')
            ->with('TestDummy', null, ['foo' => 'bar'])
            ->willReturn('abcdefgh');
        $sut = new NSKeyedUnarchiver([$hydrator]);

        $out = $unarchiveFn('objectWithSingleProp', $sut);
        $this->assertSame('abcdefgh', $out);
    }

    /**
     * @dataProvider provideArchivedDataDecoder
     */
    public function testThrowsWhenNoHydratorForObjectIsAvailable(callable $unarchiveFn)
    {
        $sut = new NSKeyedUnarchiver([]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/no hydrator for.+found/');
        $unarchiveFn('emptyObject', $sut);
    }
}
