<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO;

use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;

/**
 * @implements \IteratorAggregate<string, mixed>
 * @implements ArrayableInterface<string, mixed>
 */
final class NSIncompleteObject implements HydrationAwareInterface, \IteratorAggregate, ArrayableInterface
{
    public function __construct(
        /** @readonly */
        public string $__NSNativeClass,

        /**
         * @var array<string>|null
         * @readonly
         */
        public ?array $__NSClassChain,
    ) {}

    public static function createForHydration(string $nativeClass, ?array $classChain): static
    {
        return new static($nativeClass, $classChain);
    }

    public function toArray(): array
    {
        $out = \get_object_vars($this);
        unset($out['__NSNativeClass'], $out['__NSClassChain']);

        return $out;
    }

    /**
     * @return \ArrayIterator<string, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $k => $v) {
            $k = (string)$k;

            if (isset($this->{$k})) {
                throw new InvalidArgumentException(
                    \sprintf(
                        'Unserialized data contains property "%s" - ' .
                        'this property already exists in %s and cannot be overwritten',
                        $k, static::class
                    )
                );
            }

            $this->{$k} = $v;
        }
    }
}
