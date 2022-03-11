<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO;

interface HydrationAwareInterface
{
    /**
     * @param array<string>|null $classChain
     */
    public static function createForHydration(string $nativeClass, ?array $classChain): static;
}
