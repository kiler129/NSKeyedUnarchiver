<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Exception;


class HydrationException extends MalformedArchiveException
{
    use EnforceStableConstructorTrait;

    /**
     * @param scalar ...$params
     */
    public static function createMalformedReference(string $reason, mixed ...$params): static
    {
        return new static(\sprintf('Object reference is invalid: ' . $reason, ...$params));
    }
}
