<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Collection;

/**
 * @template TKey
 * @template TValue
 * @extends \ArrayAccess<TKey, TValue>
 * @extends \IteratorAggregate<TKey, TValue>
 */
interface ArrayLikeInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
}
