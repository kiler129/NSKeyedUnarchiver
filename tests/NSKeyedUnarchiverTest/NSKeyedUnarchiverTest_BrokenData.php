<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\NSKeyedUnarchiverTest;

use CFPropertyList\CFPropertyList;
use NoFlash\NSKeyedUnarchiver\Factory\HydratorInterface;
use NoFlash\NSKeyedUnarchiver\Exception\DanglingReferenceException;
use NoFlash\NSKeyedUnarchiver\Exception\HydrationException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\NSKeyedUnarchiver;
use NoFlash\NSKeyedUnarchiver\Validator\ArchiveValidatorInterface;

/**
 * @group Functional
 * @covers \NoFlash\NSKeyedUnarchiver\NSKeyedUnarchiver
 *
 * This class tests both binary & textual plists. While technically we don't own the plist library, this is meant to be
 * an end-to-end functional test with real files. Testing archive decoding with unit tests is pointless as we don't own
 * the data format (nor docs are available).
 * This particular class is dedicated to testing behavior of the unarchiver when unexpected/broken data is passed to it.
 *
 * @testdox NSKeyedUnarchiver with broken data
 */
class NSKeyedUnarchiverTest_BrokenData extends AbstractNSKeyedUnarchiverTest
{
    private NSKeyedUnarchiver $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSKeyedUnarchiver(
            [$this->createStub(HydratorInterface::class)],
            $this->createStub(ArchiveValidatorInterface::class)
        );
    }

    /**
     * @dataProvider provideArchivedDataDecoder
     */
    public function testRejectsInvalidPayloadValidation(callable $unarchiveFn)
    {
        $exception = new \BadFunctionCallException(); //picked some improbable one
        $validator = $this->createMock(ArchiveValidatorInterface::class);
        $validator
            ->expects($this->atLeastOnce())
            ->method('validateContainer')
            ->with($this->isInstanceOf(CFPropertyList::class))
            ->willThrowException($exception)
        ;

        $hydrator = $this->createStub(HydratorInterface::class);

        $sut = new NSKeyedUnarchiver([$hydrator], $validator);

        $this->expectExceptionObject($exception);
        $unarchiveFn('null', $sut);
    }

    /**
     * @dataProvider provideBrokenDataDecoder
     */
    public function testRejectsInvalidContainer(callable $unarchiveFn)
    {
        $this->expectException(MalformedArchiveException::class);
        $this->expectExceptionMessageMatches('/expected.*?dictionary/');
        $unarchiveFn('invalidContainer', $this->subjectUnderTest);
    }

    /**
     * @dataProvider provideBrokenDataDecoder
     */
    public function testRejectsMissingRoot(callable $unarchiveFn)
    {
        $this->expectException(MalformedArchiveException::class);
        $this->expectExceptionMessageMatches('/root.*?does not exist/');
        $unarchiveFn('missingRoot', $this->subjectUnderTest);
    }

    /**
     * @dataProvider provideBrokenDataDecoder
     */
    public function testRejectsObjectsWithInvalidObjectDeclarationContainer(callable $unarchiveFn)
    {
        $this->expectException(MalformedArchiveException::class);
        $this->expectExceptionMessageMatches('/expected.*declaration.*dict/i');
        $unarchiveFn('invalidObjectDeclarationContainer', $this->subjectUnderTest);
    }

    /**
     * @dataProvider provideBrokenDataDecoder
     */
    public function testRejectsObjectsWithNoClassNameDefined(callable $unarchiveFn)
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessageMatches('/class name is missing/');
        $unarchiveFn('objectWithNoClassName', $this->subjectUnderTest);
    }

    /**
     * @dataProvider provideBrokenDataDecoder
     */
    public function testRejectsObjectsWithNonStringClassName(callable $unarchiveFn)
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessageMatches('/class name.*not a string/');
        $unarchiveFn('objectWithNonStringClassName', $this->subjectUnderTest);
    }

    /**
     * @dataProvider provideBrokenDataDecoder
     */
    public function testRejectsObjectsWithNonArrayClassChain(callable $unarchiveFn)
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessageMatches('/class chain.*not an array/');
        $unarchiveFn('objectWithNonArrayClassChain', $this->subjectUnderTest);
    }

    /**
     * @dataProvider provideBrokenDataDecoder
     */
    public function testThrowsOnUnresolvableReferences(callable $unarchiveFn)
    {
        $this->expectException(DanglingReferenceException::class);
        $unarchiveFn('invalidReference', $this->subjectUnderTest);
    }
}
