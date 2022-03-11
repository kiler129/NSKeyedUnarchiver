<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Factory;

/**
 * @template-covariant T
 */
interface HydratorInterface
{
    /**
     * @param array<string>|null  $classChain
     * @param array<int|string, mixed> $properties
     */
    public function canHydrateObject(string $nativeClass, ?array $classChain, array $properties): bool;

    /**
     * @param array<string>|null  $classChain
     * @param array<int|string, mixed> $properties
     * @return T
     */
    public function hydrateObject(string $nativeClass, ?array $classChain, array $properties): mixed;
}
