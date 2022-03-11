<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Collection;

/**
 * @template TKey
 * @template TValue
 */
trait ArrayLikeObjectTrait
{
    /**
     * @var array<TKey, TValue>
     */
    protected array $items = [];

    /**
     * @return \ArrayIterator<TKey, TValue>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @param TKey $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param TKey $offset
     *
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * @param TKey $offset
     * @param TValue $value
     */
    abstract public function offsetSet(mixed $offset, mixed $value): void;

    /**
     * @param TKey $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
