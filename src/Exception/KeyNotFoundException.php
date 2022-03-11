<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Exception;

class KeyNotFoundException extends MalformedArchiveException
{
    use EnforceStableConstructorTrait;

    /**
     * @param array<int|string, mixed> $data
     */
    public static function createForArray(string $expectedKey, array $data): static
    {
        return new static(
            \sprintf(
                'Expected serialized data to contain %s, found keys: %s',
                $expectedKey,
                \implode(', ', \array_keys($data))
            )
        );
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public static function createForSingleKeyArray(string $expectedKey, array $data): static
    {
        return new static(
            \sprintf(
                'Expected serialized data to contain only %s, found keys: %s',
                $expectedKey,
                \implode(', ', \array_keys($data))
            )
        );
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public static function createForObjProp(string $className, string $unexpectedKey, array $data): static
    {
        return new static(
            \sprintf(
                'Serialized data for %s was not expected to contain key %s, found keys: %s',
                $className,
                $unexpectedKey,
                \implode(', ', \array_keys($data))
            )
        );
    }
}
