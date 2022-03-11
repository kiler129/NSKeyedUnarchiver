<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\ArrayableInterface;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Exception\OverflowException;

/**
 * @implements \IteratorAggregate<int, mixed>
 * @implements ArrayableInterface<int, mixed>
 * @implements SelfHydratableInterface<int, array<int, mixed>>
 */
class NSSet extends NSObject implements \IteratorAggregate, \Countable, ArrayableInterface, SelfHydratableInterface
{
    public const NATIVE_CLASS = 'NSSet';

    /** @var array<int, mixed> */
    protected array $items = [];

    public function __unserialize(array $data): void
    {
        if (\count($data) !== 1 || !isset($data['NS.objects'])) {
            throw KeyNotFoundException::createForSingleKeyArray('NS.objects', $data);
        }

        try {
            foreach ($data['NS.objects'] as $item) {
                $this->addItem($item);
            }
        } catch (OverflowException $e) {
            throw new MalformedArchiveException(
                \sprintf('Archive contains an %s which contains non-unique values', self::NATIVE_CLASS), 0, $e
            );
        }
    }

    public function addItem(mixed $item): void
    {
        if (\in_array($item, $this->items, true)) {
            throw new OverflowException(
                \sprintf(
                    'Item %s{%s} already exists in the set',
                    \is_object($item) ? $item::class : \gettype($item),
                    (\is_scalar($item) ? (string)$item : '<opaque>')
                )
            );
        }

        $this->items[] = $item;
    }

    /**
     * @return \ArrayIterator<int, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
