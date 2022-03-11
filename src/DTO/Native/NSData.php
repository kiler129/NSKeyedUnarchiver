<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;

/**
 * @implements SelfHydratableInterface<string, string|null>
 */
class NSData extends NSObject implements SelfHydratableInterface
{
    use FlatNSUnserializerTrait;

    public const NATIVE_CLASS = 'NSData';

    public ?string $data = null;

    public function __toString(): string
    {
        return $this->data ?? '';
    }
}
