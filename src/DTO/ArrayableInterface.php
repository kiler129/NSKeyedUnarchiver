<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO;

/**
 * @template TKey
 * @template TVal
 */
interface ArrayableInterface
{
    /**
     * Provides object's array representaiton
     *
     * @return array<TKey, TVal>
     */
    public function toArray(): array;
}
