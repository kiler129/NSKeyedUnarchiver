<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;

/**
 * @implements SelfHydratableInterface<string, string>
 */
class NSUUID extends NSObject implements SelfHydratableInterface
{
    use FlatNSUnserializerTrait;

    public const NATIVE_CLASS = 'NSUUID';

    /**
     * @var string Binary representation of the UUID. You can use Ramsey\Uuid\Uuid::fromBytes() or similar to convert it
     */
    public string $uuidbytes;
}
