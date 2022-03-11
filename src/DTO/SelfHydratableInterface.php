<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO;

/**
 * @template TKey
 * @template TValue
 */
interface SelfHydratableInterface
{
    /**
     * @param array<TKey, TValue> $data
     */
    public function __unserialize(array $data): void;
}
