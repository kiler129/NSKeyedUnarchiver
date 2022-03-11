<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver;

use CFPropertyList\CFArray;
use CFPropertyList\CFDictionary;
use CFPropertyList\CFPropertyList;
use CFPropertyList\CFType;
use CFPropertyList\CFUid;
use NoFlash\NSKeyedUnarchiver\Factory\HydratorInterface;
use NoFlash\NSKeyedUnarchiver\Exception\DanglingReferenceException;
use NoFlash\NSKeyedUnarchiver\Exception\HydrationException;
use NoFlash\NSKeyedUnarchiver\Exception\InvalidArgumentException;
use NoFlash\NSKeyedUnarchiver\Exception\LogicException;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Factory\ArrayHydrator;
use NoFlash\NSKeyedUnarchiver\Factory\ClassMapHydrator;
use NoFlash\NSKeyedUnarchiver\Factory\UniversalObjectHydrator;
use NoFlash\NSKeyedUnarchiver\Validator\ArchiveFormatValidator;
use NoFlash\NSKeyedUnarchiver\Validator\ArchiveValidatorInterface;

/**
 * @template T
 */
class NSKeyedUnarchiver
{
    protected const DEHYDRATED_OBJ_TKEY = '$class';
    protected const DEHYDRATED_META_CN = '$classname';
    protected const DEHYDRATED_META_CHAIN = '$classes';
    /** @see https://developer.apple.com/documentation/foundation/nskeyedarchiver/keyed_archiver_root_object_key */
    protected const NSKEYEDARCHIVE_ROOT_OBJ_KEY = 'root';

    private ArchiveValidatorInterface $kaValidator;

    /** @var array<HydratorInterface<T>> */
    private array $hydrators = [];

    /**
     * @var array<int, mixed> Used to ensure references from $objects are created only once (to preserve pointers)
     */
    private array $objectRefCache = [];

    /**
     * @param iterable<HydratorInterface<T>> $hydrators
     */
    final public function __construct(iterable $hydrators = [], ArchiveValidatorInterface $archiveValidator = null)
    {
        $this->kaValidator = $archiveValidator ?? new ArchiveFormatValidator();
        $this->populateHydrators($hydrators);
    }

    public static function createWithNativeTypesCasting(): static
    {
        return new static([
            ClassMapHydrator::createWithNativeTypes(),
            new UniversalObjectHydrator(),
        ]);
    }

    public static function createWithArrayCasting(): static
    {
        return new static([new ArrayHydrator()]);
    }

    /**
     * @return mixed|T
     */
    public function unarchiveRootFromFile(string $file, int $fileType = CFPropertyList::FORMAT_AUTO): mixed
    {
        return $this->unarchiveRootFromPropertyList(new CFPropertyList($file, $fileType));
    }

    /**
     * @return mixed|T
     */
    public function unarchiveRootFromPropertyList(CFPropertyList $plist): mixed
    {
        $raw = $this->unarchiveFromPropertyList($plist);
        if (!\is_array($raw)) {
            throw new MalformedArchiveException(
                'Archive container was expected to be a dictionary/array, but found ' .
                \gettype($raw)
            );
        }

        if (!array_key_exists(self::NSKEYEDARCHIVE_ROOT_OBJ_KEY, $raw)) {
            throw new MalformedArchiveException(
                \sprintf(
                    'Archive root (NSKeyedArchiveRootObjectKey "%s") does not exist in the main container, found keys: %s',
                    self::NSKEYEDARCHIVE_ROOT_OBJ_KEY, \implode(', ', \array_keys($raw))
                )
            );
        }

        return $raw[self::NSKEYEDARCHIVE_ROOT_OBJ_KEY];
    }

    /**
     * @return mixed|T
     */
    public function unarchiveRootFromString(string $string, int $fileType = CFPropertyList::FORMAT_AUTO): mixed
    {
        $plist = new CFPropertyList();
        $plist->parse($string, $fileType);

        return $this->unarchiveRootFromPropertyList($plist);
    }

    /**
     *
     * This may become public in the future. However, there's little practical use to non-rooted archives and I've never
     * seen them in the wild.
     *
     * @return T|array<T>|mixed|null
     */
    private function unarchiveFromPropertyList(CFPropertyList $plist): mixed
    {
        $this->clearReferenceCache();

        try {
            $this->kaValidator->validateContainer($plist);
            /** @var array{
             *     '$version': \CFPropertyList\CFNumber,
             *     '$archiver': \CFPropertyList\CFString,
             *     '$top': \CFPropertyList\CFDictionary,
             *     '$objects': \CFPropertyList\CFArray
             * } $plistDict */
            $plistDict = $plist->getValue()->getValue(); //@phpstan-ignore-line validator ensures the array is correct

            return $this->walkStructure($plistDict['$objects'], $plistDict['$top']);
        } finally {
            $this->clearReferenceCache();
        }
    }

    private function clearReferenceCache(): void
    {
        $this->objectRefCache = [];
    }

    /**
     * @param iterable<HydratorInterface<T>> $hydrators
     */
    private function populateHydrators(iterable $hydrators): void
    {
        $idx = 0;
        foreach ($hydrators as $hydrator) {
            if ($hydrator instanceof HydratorInterface) {
                $this->hydrators[] = $hydrator;
                ++$idx;
                continue;
            }

            throw new InvalidArgumentException(
                \sprintf(
                    'Hydrator #%d %s does is not an instance of %s',
                    $idx,
                    $hydrator::class,
                    HydratorInterface::class
                )
            );
        }
    }

    /**
     * @param array<string>|null $classChain
     * @param array<int|string, mixed> $properties
     *
     * @return HydratorInterface<T>
     */
    private function getHydratorForObject(string $nativeClass, ?array $classChain, array $properties): HydratorInterface
    {
        foreach ($this->hydrators as $hydrator) {
            if ($hydrator->canHydrateObject($nativeClass, $classChain, $properties)) {
                return $hydrator;
            }
        }

        throw new LogicException(
            \sprintf(
                '%s instance is misconfiguration - no hydrator for %s (%s) was found. ' .
                'You should define at least one universal hydrator (e.g. %s)',
                static::class,
                $nativeClass,
                $classChain === null ? '<unknown chain>' : \implode(' => ', $classChain),
                UniversalObjectHydrator::class
            )
        );
    }

    /**
     * @param array<string, CFType> $data
     *
     * @return T
     */
    private function recreateObject(CFArray $objects, array $data): mixed
    {
        $classDef = $this->walkStructure($objects, $data[self::DEHYDRATED_OBJ_TKEY]);
        if (!\is_array($classDef)) {
            throw new MalformedArchiveException('Expected object declaration to be a dictionary, got ' . gettype($classDef));
        }

        //Some sanity check of the metadata is needed
        if (!isset($classDef[self::DEHYDRATED_META_CN])) {
            throw HydrationException::createMalformedReference('class name is missing');
        }
        if (!is_string($classDef[self::DEHYDRATED_META_CN])) {
            throw HydrationException::createMalformedReference(
                'class name is not a string (found %s)',
                \gettype($classDef[self::DEHYDRATED_META_CN])
            );
        }
        $nativeClass = $classDef[self::DEHYDRATED_META_CN];

        $classChain = $classDef[self::DEHYDRATED_META_CHAIN] ?? null;
        if ($classChain !== null && !\is_array($classChain)) {
            throw HydrationException::createMalformedReference(
                'class chain is not an array (found %s)',
                \gettype($classDef[self::DEHYDRATED_META_CHAIN])
            );
        }

        $properties = [];
        foreach ($data as $k => $v) {
            if ($k === self::DEHYDRATED_OBJ_TKEY) {
                continue; //this technically can be done with unset($data[...]) but we want to avoid CoW memory hog here
            }

            $properties[$k] = $this->walkStructure($objects, $v);
        }

        return $this->getHydratorForObject($nativeClass, $classChain, $properties)
                    ->hydrateObject($nativeClass, $classChain, $properties);
    }

    /**
     * @return T|array<T>|mixed|null
     */
    protected function walkStructure(CFArray $objects, CFType $struct): mixed
    {
        $structVal = $struct->getValue();

        if ($struct instanceof CFUid) {
            /** @var int $structVal */
            return $this->walkReference($objects, $structVal);
        }

        if ($structVal === '$null') { //null is encoded as flat CFString with "$null" inside or as NSNull (in Swift)
            return null;
        }

        //todo: this probably should check for bytes to decompress embedded plist

        if ($struct instanceof CFDictionary) {
            /** @var array<string, CFType> $structVal */
            if (isset($structVal[self::DEHYDRATED_OBJ_TKEY])) { //Special NSKeyedArchiver marker
                return $this->recreateObject($objects, $structVal);
            }

            //If this isn't a packed class we always translate it to a native PHP's array (not trying to create NSArray
            // here as it is not the object which was packed)
            $out = [];
            foreach ($structVal as $k => $v) {
                $out[$k] = $this->walkStructure($objects, $v);
            }

            return $out;
        }

        if ($struct instanceof CFArray) {
            /** @var array<int, CFType> $structVal */
            $out = [];
            foreach ($structVal as $k => $v) {
                $out[$k] = $this->walkStructure($objects, $v);
            }

            return $out;
        }

        if (\is_scalar($structVal)) { //strings and simple numbers are encoded flat without e.g. NSNumber
            return $structVal;
        }

        throw new MalformedArchiveException(
            \sprintf(
                'Unexpected wrapped type %s found while resolving %s structure',
                (is_object($structVal) ? $structVal::class : gettype($structVal)),
                $struct::class
            )
        );
    }

    protected function walkReference(CFArray $objects, int $refNo): mixed
    {
        if (!\array_key_exists($refNo, $this->objectRefCache)) {
            /** @var CFType|null $object */
            $object = $objects->get($refNo);
            if ($object === null) {
                throw new DanglingReferenceException(
                    \sprintf('References pointing to object at index %d is invalid', $refNo)
                );
            }

            $this->objectRefCache[$refNo] = $this->walkStructure($objects, $object);
        }

        return $this->objectRefCache[$refNo];
    }
}
