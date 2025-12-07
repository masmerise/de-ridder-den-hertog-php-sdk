<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Failure;

use Throwable;

final class UnknownException extends DeRidderDenHertogException
{
    public static function sorry(Throwable $previous): self
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }
}
