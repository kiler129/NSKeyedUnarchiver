<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Exception;

trait EnforceStableConstructorTrait
{
    final public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
