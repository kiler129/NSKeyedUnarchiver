<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Factory;

use NoFlash\NSKeyedUnarchiver\Factory\HydratorInterface;

/**
 * @implements HydratorInterface<array<mixed>>
 */
class ArrayHydrator implements HydratorInterface
{
    public function __construct(
        public ?string $classNameKey = null,
        public ?string $classChainKey = null
    ) { }

    public function canHydrateObject(string $nativeClass, ?array $classChain, array $properties): bool
    {
        return true;
    }

    public function hydrateObject(string $nativeClass, ?array $classChain, array $properties): array
    {
        if ($this->classNameKey !== null) {
            $properties[$this->classNameKey] = $nativeClass;
        }

        if ($this->classChainKey !== null) {
            $properties[$this->classChainKey] = $classChain;
        }

        return $properties;
    }
}
