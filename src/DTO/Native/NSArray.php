<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Collection\ArrayLikeInterface;
use NoFlash\NSKeyedUnarchiver\DTO\Collection\ArrayLikeObjectTrait;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;

/**
 * @implements ArrayLikeInterface<int, mixed>
 * @implements SelfHydratableInterface<string, array<int, mixed>>
 */
class NSArray extends NSObject implements ArrayLikeInterface, SelfHydratableInterface
{
    /** @use ArrayLikeObjectTrait<int, mixed> */
    use ArrayLikeObjectTrait;

    public const NATIVE_CLASS = 'NSArray';

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
            return;
        }

        if (isset($this->items[$offset])) {
            $this->items[$offset] = $value;
            return;
        }

        /** @var int|null $lastKey */
        $lastKey = \array_key_last($this->items);
        if (($lastKey === null && $offset === 0) || (int)($lastKey)+1 === $offset) {
            $this->items[$offset] = $value;
            return;
        }

        throw new InvalidArgumentException(
            \sprintf(
                'Cannot set value at offset %d of %s. Pure lists require consecutive 0-n keys - ' .
                'you can override already existing offset, null-offset to set the next available one (%d), or ' .
                'specify the next available offset manually (%d)',
                (string)$offset, static::class, (int)($lastKey)+1, (int)($lastKey)+1
            )
        );
    }

    public function offsetUnset(mixed $offset): void
    {
        if (isset($this->items[$offset]) && \array_key_last($this->items) !== $offset) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Cannot remove value at offset %d of %s. Pure lists require consecutive 0-n keys - ' .
                    'you can only remove the last element (count()-1 => %d)',
                    (string)$offset, static::class, \array_key_last($this->items)
                )
            );
        }

        unset($this->items[$offset]);
    }

    public function __unserialize(array $data): void
    {
        if (\count($data) !== 1 || !isset($data['NS.objects'])) {
            throw KeyNotFoundException::createForSingleKeyArray('NS.objects', $data);
        }

        if (!\array_is_list($data['NS.objects'])) {
            throw new MalformedArchiveException(
                \sprintf('NS.objects for %s must be a pure list (all numeric keys, 0 to count()-1', static::class)
            );
        }

        $this->items = $data['NS.objects'];
    }
}
