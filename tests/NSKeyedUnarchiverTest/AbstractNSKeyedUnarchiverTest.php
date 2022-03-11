<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\NSKeyedUnarchiverTest;

use CFPropertyList\CFPropertyList;
use NoFlash\NSKeyedUnarchiver\NSKeyedUnarchiver;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

abstract class AbstractNSKeyedUnarchiverTest extends TestCase
{
    protected function provideDataDecoder(string $type)
    {
        $binPath = __DIR__ . "/../../resources/tests/$type/%s.bin.plist";
        $xmlPath = __DIR__ . "/../../resources/tests/$type/%s.xml.plist";

        //NSKeyedUnarchiver is given a file path
        yield 'from binary file with format detection' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromFile(\sprintf($binPath, $file)),
        ];
        yield 'from xml file with format detection' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromFile(\sprintf($xmlPath, $file)),
        ];

        yield 'from binary file with static format' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromFile(
                \sprintf($binPath, $file),
                CFPropertyList::FORMAT_BINARY
            ),
        ];
        yield 'from xml file with static format' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromFile(
                \sprintf($xmlPath, $file),
                CFPropertyList::FORMAT_XML
            ),
        ];

        //NSKeyedUnarchiver is given a file contents
        yield 'from binary string with format detection' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromString(
                \file_get_contents(\sprintf($binPath, $file))
            ),
        ];
        yield 'from xml string with format detection' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromString(
                \file_get_contents(\sprintf($xmlPath, $file))
            ),
        ];

        yield 'from binary string with static format' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromString(
                \file_get_contents(\sprintf($binPath, $file)),
                CFPropertyList::FORMAT_BINARY
            ),
        ];
        yield 'from xml string with static format' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromString(
                \file_get_contents(\sprintf($xmlPath, $file)),
                CFPropertyList::FORMAT_XML
            ),
        ];

        //NSKeyedUnarchiver is given a CFPropertyList
        yield 'from CFPropertyList from binary' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromPropertyList(
                (new CFPropertyList(\sprintf($binPath, $file), CFPropertyList::FORMAT_BINARY))
            ),
        ];
        yield 'from CFPropertyList from xml' => [
            fn(string $file, NSKeyedUnarchiver $ins) => $ins->unarchiveRootFromPropertyList(
                (new CFPropertyList(\sprintf($xmlPath, $file), CFPropertyList::FORMAT_XML))
            ),
        ];
    }

    public function provideArchivedDataDecoder()
    {
        return $this->provideDataDecoder('ArchivedData');
    }

    public function provideBrokenDataDecoder()
    {
        return $this->provideDataDecoder('BrokenData');
    }
}
