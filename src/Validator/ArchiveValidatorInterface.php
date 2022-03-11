<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Validator;

use CFPropertyList\CFPropertyList;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;

interface ArchiveValidatorInterface
{
    /**
     * @throws MalformedArchiveException
     */
    public function validateContainer(CFPropertyList $plist): void;
}
