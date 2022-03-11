<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;

/**
 * @implements SelfHydratableInterface<string, string|null>
 */
class NSRegularExpression extends NSObject implements SelfHydratableInterface
{
    public const NATIVE_CLASS = 'NSRegularExpression';

    public ?string $pattern = null;
    public int $options = 0;

    public function __unserialize(array $data): void
    {
        if (\count($data) !== 2 || !\array_key_exists('NSPattern', $data) || !isset($data['NSOptions'])) {
            throw new MalformedArchiveException(
                \sprintf(
                    'Expected serialized data with NSPattern and NSOptions keys only, got keys: %s',
                    \implode(', ', \array_keys($data))
                )
            );
        }

        if (!\is_string($data['NSPattern']) && $data['NSPattern'] !== null) {
            throw new MalformedArchiveException(
                'Expected NSPattern to be a string or null, got ' . \gettype($data['NSPattern'])
            );
        }

        if (!\is_int($data['NSOptions'])) {
            throw new MalformedArchiveException(
                'Expected NSOptions to be an integer, got ' . \gettype($data['NSOptions'])
            );
        }

        $this->pattern = $data['NSPattern'];
        $this->options = $data['NSOptions'];
    }
}
