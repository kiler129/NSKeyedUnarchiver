<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\Validator;

use CFPropertyList\CFArray;
use CFPropertyList\CFDictionary;
use CFPropertyList\CFNumber;
use CFPropertyList\CFPropertyList;
use CFPropertyList\CFString;
use CFPropertyList\CFType;
use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Validator\ArchiveFormatValidator;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\Validator\ArchiveFormatValidator
 */
class ArchiveFormatValidatorTest extends TestCase
{
    private ArchiveFormatValidator $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new ArchiveFormatValidator();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testValidContainerPassesValidation()
    {
        $container = new CFPropertyList();
        $root = new CFDictionary();
        $root->add('$version', new CFNumber(100_000));
        $root->add('$archiver', new CFString('NSKeyedArchiver'));
        $root->add('$objects', new CFArray());
        $root->add('$top', new CFDictionary());
        $container->add($root);

        $this->subjectUnderTest->validateContainer($container);
    }

    public function testContainerWithoutVersionFailsValidation()
    {
        $container = new CFPropertyList();
        $root = new CFDictionary();
        $root->add('$archiver', new CFString('NSKeyedArchiver'));
        $root->add('$objects', new CFArray());
        $root->add('$top', new CFDictionary());
        $container->add($root);

        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->validateContainer($container);
    }

    public function testContainerWithoutArchiverFailsValidation()
    {
        $container = new CFPropertyList();
        $root = new CFDictionary();
        $root->add('$version', new CFNumber(100_000));
        $root->add('$objects', new CFArray());
        $root->add('$top', new CFDictionary());
        $container->add($root);

        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->validateContainer($container);
    }

    public function testContainerWithUnknownArchiverFailsValidation()
    {
        $container = new CFPropertyList();
        $root = new CFDictionary();
        $root->add('$version', new CFNumber(100_000));
        $root->add('$archiver', new CFString('NSCustomArchiver'));
        $root->add('$objects', new CFArray());
        $root->add('$top', new CFDictionary());
        $container->add($root);

        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->validateContainer($container);
    }

    public function testContainerWithoutObjectsListFailsValidation()
    {
        $container = new CFPropertyList();
        $root = new CFDictionary();
        $root->add('$version', new CFNumber(100_000));
        $root->add('$archiver', new CFString('NSKeyedArchiver'));
        $root->add('$top', new CFDictionary());
        $container->add($root);

        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->validateContainer($container);
    }

    public function testContainerWithoutHeadFailsValidation()
    {
        $container = new CFPropertyList();
        $root = new CFDictionary();
        $root->add('$version', new CFNumber(100_000));
        $root->add('$archiver', new CFString('NSKeyedArchiver'));
        $root->add('$objects', new CFArray());
        $container->add($root);

        $this->expectException(KeyNotFoundException::class);
        $this->subjectUnderTest->validateContainer($container);
    }

    public function provideInvalidArchiveVersion()
    {
        return [
            'too old' => [new CFNumber(99_999)],
            'too new' => [new CFNumber(100_001)],
            'not numeric' => [new CFString('10000')],
        ];
    }

    /**
     * @dataProvider provideInvalidArchiveVersion
     */
    public function testContainerWithUnpexpectedArchiverVersionFailsValidation(CFType $version)
    {
        $container = new CFPropertyList();
        $root = new CFDictionary();
        $root->add('$version', $version);
        $root->add('$archiver', new CFString('NSKeyedArchiver'));
        $root->add('$objects', new CFArray());
        $root->add('$top', new CFDictionary());
        $container->add($root);

        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->validateContainer($container);
    }

    public function testContainerWithNonDictionaryRootFailsValidation()
    {
        $container = new CFPropertyList();
        $root = new CFArray();
        $container->add($root);

        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->validateContainer($container);
    }
}
