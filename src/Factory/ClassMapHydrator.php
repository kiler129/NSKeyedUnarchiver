<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Factory;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSArray;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSData;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSDate;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSDictionary;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableArray;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableData;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSMutableDictionary;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSNull;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSRegularExpression;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSSet;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSURL;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSUUID;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\OverflowException;
use NoFlash\NSKeyedUnarchiver\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @extends AbstractObjectHydrator<object>
 */
class ClassMapHydrator extends AbstractObjectHydrator
{
    /** @var array<string, class-string>  */
    private array $classMap = [];

    /**
     * @param iterable<string, class-string>|null $classMap
     */
    final public function __construct(?PropertyAccessor $propertyAccessor = null, iterable $classMap = null)
    {
        $this->allowDynamicProperties = false;
        parent::__construct($propertyAccessor);

        if ($classMap !== null) {
            $this->setClassMap($classMap);
        }
    }

    /**
     * @param class-string $fqcn
     */
    public function addMappedClass(string $nativeClass, string $fqcn): void
    {
        if (isset($this->classMap[$nativeClass]) && $this->classMap[$nativeClass] !== $fqcn) {
            throw new OverflowException(
                \sprintf(
                    'Cannot map %s to %s - %1$s is already mapped to %s',
                    $nativeClass,
                    $fqcn,
                    $this->classMap[$nativeClass]
                )
            );
        }

        if (!\class_exists($fqcn)) {
            throw new RuntimeException(
                \sprintf('Cannot map native class %s to %s - target class does not exist', $nativeClass, $fqcn)
            );
        }

        $this->classMap[$nativeClass] = $fqcn;
    }

    /**
     * @param iterable<string, class-string> $classMap
     */
    public function setClassMap(iterable $classMap): void
    {
        $oldMap = $this->classMap;
        $this->classMap = [];
        try {
            foreach ($classMap as $native => $fqcn) {
                $this->addMappedClass($native, $fqcn);
            }
        } catch (\Throwable $t) {
            $this->classMap = $oldMap;
            throw $t;
        }
    }

    public function canHydrateObject(string $nativeClass, ?array $classChain, array $properties): bool
    {
        return isset($this->classMap[$nativeClass]);
    }

    public function hydrateObject(string $nativeClass, ?array $classChain, array $properties): object
    {
        if (!isset($this->classMap[$nativeClass])) {
            throw new InvalidArgumentException(
                \sprintf('Class %s specified in "nativeClass" is not mapped', $nativeClass)
            );
        }

        return $this->hydrateAnyObject($this->classMap[$nativeClass], $nativeClass, $classChain, $properties);
    }

    public static function createWithNativeTypes(): static
    {
        return new static(null, [
            NSArray::NATIVE_CLASS => NSArray::class,
            NSData::NATIVE_CLASS => NSData::class,
            NSDate::NATIVE_CLASS => NSDate::class,
            NSDictionary::NATIVE_CLASS => NSDictionary::class,
            NSMutableArray::NATIVE_CLASS => NSMutableArray::class,
            NSMutableData::NATIVE_CLASS => NSMutableData::class,
            NSMutableDictionary::NATIVE_CLASS => NSMutableDictionary::class,
            NSNull::NATIVE_CLASS => NSNull::class,
            NSRegularExpression::NATIVE_CLASS => NSRegularExpression::class,
            NSSet::NATIVE_CLASS => NSSet::class,
            NSURL::NATIVE_CLASS => NSURL::class,
            NSUUID::NATIVE_CLASS => NSUUID::class,
        ]);
    }
}
