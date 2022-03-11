<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Factory;

use NoFlash\NSKeyedUnarchiver\DTO\HydrationAwareInterface;
use NoFlash\NSKeyedUnarchiver\Factory\HydratorInterface;
use NoFlash\NSKeyedUnarchiver\DTO\SelfHydratableInterface;
use NoFlash\NSKeyedUnarchiver\Exception\DomainException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template-covariant T of object
 * @implements HydratorInterface<T>
 * @internal
 */
abstract class AbstractObjectHydrator implements HydratorInterface
{
    protected PropertyAccessorInterface $propAcc;

    /**
     * @var bool Whether to allow dynamic properties creation on objects
     */
    protected bool $allowDynamicProperties = true;

    public function __construct(?PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propAcc = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param class-string $fqcn
     * @param array<string> $classChain
     * @param array<string|int, mixed> $properties
     *
     * @return T
     */
    protected function hydrateAnyObject(string $fqcn, string $nativeClass, ?array $classChain, array $properties): object
    {
        $obj = (\is_subclass_of($fqcn, HydrationAwareInterface::class))
            ? $fqcn::createForHydration($nativeClass, $classChain)
            : new $fqcn();

        if ($obj instanceof SelfHydratableInterface) {
            $obj->__unserialize($properties);
            return $obj;
        }

        foreach ($properties as $k => $v) {
            $k = (string)$k;

            try {
                $this->propAcc->setValue($obj, $k, $v);
                /** @var object $obj */
            } catch (NoSuchPropertyException $e) {
                //This is kind of a workaround for https://github.com/symfony/symfony/issues/45681
                if ($this->allowDynamicProperties) {
                    $obj->{$k} = $v; //@phpstan-ignore-line
                    continue;
                }

                throw new DomainException(
                    \sprintf(
                        'Cannot hydrate native "%s" object to %s - property "%s" does not exist on %s, and ' .
                        'dynamic properties creation has been disabled in %s',
                        $nativeClass, $fqcn, $k, $obj::class, static::class
                    ),
                    0, $e
                );
            }

        }

        return $obj;
    }
}
