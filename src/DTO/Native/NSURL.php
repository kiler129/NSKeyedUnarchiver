<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;

/**
 * @implements SelfHydratableInterface<string, string|null>
 */
class NSURL extends NSObject implements SelfHydratableInterface
{
    use FlatNSUnserializerTrait;

    public const NATIVE_CLASS = 'NSURL';

    public ?string $base = null;
    public ?string $relative = null;
}
