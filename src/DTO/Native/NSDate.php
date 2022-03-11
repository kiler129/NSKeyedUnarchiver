<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;

/**
 * @implements SelfHydratableInterface<string, int>
 */
class NSDate extends NSObject implements SelfHydratableInterface
{
    use FlatNSUnserializerTrait;

    public const NATIVE_CLASS = 'NSDate';
    /** @see https://developer.apple.com/documentation/corefoundation/cfabsolutetime */
    private const CF_TO_UNIX_OFFSET = 978307200; //seconds

    public \DateTimeInterface $dateTime;

    public function __unserialize(array $data): void
    {
        if (\count($data) !== 1 || !isset($data['NS.time'])) {
            throw KeyNotFoundException::createForSingleKeyArray('NS.time', $data);
        }

        $dt = \is_numeric($data['NS.time']) ? \DateTime::createFromFormat(
            'U.u',
            \sprintf('%.6f', ((float)$data['NS.time']) + self::CF_TO_UNIX_OFFSET)
        ) : false;

        if ($dt === false) {
            throw new MalformedArchiveException(
                \sprintf(
                    'Encapsulated value of time "%s" in %s is invalid',
                    (string)$data['NS.time'],
                    self::NATIVE_CLASS
                )
            );
        }

        $this->dateTime = $dt;
    }
}
