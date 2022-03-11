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
 * @implements ArrayLikeInterface<string|int, mixed>
 * @implements SelfHydratableInterface<string, array<int, mixed>>
 */
class NSDictionary extends NSObject implements ArrayLikeInterface, SelfHydratableInterface
{
    /** @use ArrayLikeObjectTrait<string|int, mixed> */
    use ArrayLikeObjectTrait;

    public const NATIVE_CLASS = 'NSDictionary';

    /**
     * @param string $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            $this->throwInvalidKeyType($offset);
        }

        $this->items[$offset] = $value;
    }

    public function __unserialize(array $data): void
    {
        if (\count($data) !== 2 || !isset($data['NS.keys'], $data['NS.objects'])) {
            throw new KeyNotFoundException(
                'Dehydrated data is expected to contain only NS.keys and NS.objects, found keys: ' .
                \implode(', ', \array_keys($data))
            );
        }

        if (!\is_array($data['NS.keys'])) {
            throw new MalformedArchiveException(
                'NSDictionary dehydrated values definition (NS.keys) must be an array, got ' .
                \gettype($data['NS.keys'])
            );
        }

        if (!\is_array($data['NS.objects'])) {
            throw new MalformedArchiveException(
                'NSDictionary dehydrated values definition (NS.objects) must be an array, got ' .
                \gettype($data['NS.objects'])
            );
        }


        if (\count($data['NS.keys']) !== \count($data['NS.objects'])) {
            throw new MalformedArchiveException(
                \sprintf(
                    'The number of NS.keys and NS.objects isn\'t equal (%d != %d)',
                    \count($data['NS.keys']),
                    \count($data['NS.objects'])
                )
            );
        }

        foreach ($data['NS.keys'] as $idx => $key) {
            if (!\is_string($key)) {
                $this->throwInvalidKeyType($idx);
            }

            $this->items[$key] = $data['NS.objects'][$idx];
        }
    }

    private function throwInvalidKeyType(mixed $key): void
    {
        throw new InvalidArgumentException('Dictionaries allow only string key - found ' . gettype($key));
    }
}
