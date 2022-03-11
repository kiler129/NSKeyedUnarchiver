<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;

/**
 * @implements SelfHydratableInterface<mixed, mixed>
 */
final class NSNull extends NSObject implements SelfHydratableInterface
{
    public const NATIVE_CLASS = 'NSNull';

    public function __unserialize(array $data): void
    {
        if (\count($data) > 0) {
            throw new MalformedArchiveException(
                \sprintf(
                    '%s is not expected to contain any data, found keys: %s',
                    self::NATIVE_CLASS,
                    \implode(', ', \array_keys($data))
                )
            );
        }
    }
}
