<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Factory;

use NoFlash\NSKeyedUnarchiver\DTO\NSIncompleteObject;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @template T of object
 * @extends AbstractObjectHydrator<T>
 */
class UniversalObjectHydrator extends AbstractObjectHydrator
{
    /** @var class-string<T> */
    private string $targetFQCN;

    /**
     * @param class-string<T> $targetFQCN
     */
    public function __construct(string $targetFQCN = NSIncompleteObject::class, bool $allowDynamicProperties = true)
    {
        $paccBldr = PropertyAccess::createPropertyAccessorBuilder();
        $paccBldr->enableExceptionOnInvalidPropertyPath();
        parent::__construct($paccBldr->getPropertyAccessor());

        $this->allowDynamicProperties = $allowDynamicProperties;
        $this->setTargetClass($targetFQCN);
    }

    /**
     * @param class-string<T> $fqcn
     */
    public function setTargetClass(string $fqcn): void
    {
        if (!\class_exists($fqcn)) {
            throw new InvalidArgumentException(
                \sprintf('Cannot set target class to to %s - class is not loadable', $fqcn)
            );
        }

        $this->targetFQCN = $fqcn;
    }

    public function canHydrateObject(string $nativeClass, ?array $classChain, array $properties): bool
    {
        return true;
    }

    /**
     * @return T
     */
    public function hydrateObject(string $nativeClass, ?array $classChain, array $properties): object
    {
        return $this->hydrateAnyObject($this->targetFQCN, $nativeClass, $classChain, $properties);
    }
}
