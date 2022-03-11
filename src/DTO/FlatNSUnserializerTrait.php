<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO;

use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;

/**
 * @implements SelfHydratableInterface<string, mixed>
 */
trait FlatNSUnserializerTrait
{
    public function __unserialize(array $data): void
    {
        foreach ($data as $k => $v) {
            if (!\is_string($k) || \strpos($k, 'NS.') !== 0) {
                throw new MalformedArchiveException(
                    \sprintf(
                        'Expected string key starting with "NS.", got %s{%s}',
                        gettype($k),
                        $k
                    )
                );
            }

            $prop = \substr($k, 3);

            if (!\property_exists($this, $prop)) {
                throw KeyNotFoundException::createForObjProp(static::NATIVE_CLASS, $k, $data);
            }

            try {
                $this->$prop = $v;
            } catch (\TypeError $e) {
                throw new MalformedArchiveException(
                    \sprintf(
                        'Data hydrated from %s property is of invalid type - expected %s but got %s',
                        $k,
                        (new \ReflectionProperty($this, $prop))->getType()?->getName(),
                        \gettype($v)
                    ),
                    0,
                    $e
                );
            }
        }
    }
}
