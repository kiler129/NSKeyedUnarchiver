<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Validator;

use CFPropertyList\CFArray;
use CFPropertyList\CFDictionary;
use CFPropertyList\CFNumber;
use CFPropertyList\CFPropertyList;
use CFPropertyList\CFString;
use CFPropertyList\CFType;
use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;

class ArchiveFormatValidator implements ArchiveValidatorInterface
{
    public function validateContainer(CFPropertyList $plist): void
    {
        $container = $plist->getValue();
        if (!($container instanceof CFDictionary)) {
            throw new MalformedArchiveException(
                'Expected CFDictionary root element - got ' .
                (is_object($container) ? $container::class : gettype($container))
            );
        }

        $this->validateRootKeys($container);
    }

    private function validateRootKeys(CFDictionary $root): void
    {
        /** @var array<string, mixed> $values */
        $values = $root->getValue();

        $this->validateRootKeyType($values, '$version', CFNumber::class);
        $this->validateRootKeyValue($values, '$version', 100_000); //@phpstan-ignore-line
        $this->validateRootKeyType($values, '$archiver', CFString::class);
        $this->validateRootKeyValue($values, '$archiver', 'NSKeyedArchiver'); //@phpstan-ignore-line
        $this->validateRootKeyType($values, '$top', CFDictionary::class);
        $this->validateRootKeyType($values, '$objects', CFArray::class);
    }

    /**
     * @param array<string, mixed> $data
     * @param string $key
     * @param class-string $expectedType
     */
    private function validateRootKeyType(array $data, string $key, string $expectedType): void
    {
        if (!isset($data[$key])) {
            throw new KeyNotFoundException(
                \sprintf(
                    'Root CFDictionary does not have a key named %s (found: %s)',
                    $key,
                    \implode(', ', \array_keys($data))
                )
            );
        }

        if ($data[$key] instanceof $expectedType) {
            return;
        }

        throw new MalformedArchiveException(
            \sprintf(
                'Root CFDictionary->%s is expected to be %s, found %s instead',
                $key,
                $expectedType,
                is_object($data[$key]) ? $data[$key]::class : gettype($data[$key])
            )
        );
    }

    /**
     * @param array<string, CFType> $data
     */
    private function validateRootKeyValue(array $data, string $key, string|int|float|null $expectedVal): void
    {
        $rawVal = $data[$key]->getValue();
        if ($rawVal === $expectedVal) {
            return;
        }

        throw new MalformedArchiveException(
            \sprintf(
                'Root CFDictionary->%s is expected to contain %s{%s}, found %s{%s} instead',
                $key,
                \gettype($expectedVal),
                $expectedVal,
                \gettype($rawVal),
                (is_scalar($rawVal) ? (string)$rawVal : '<opaque>')
            )
        );
    }
}
