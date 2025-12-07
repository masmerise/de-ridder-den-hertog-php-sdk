<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Failure;

abstract class ValidationException extends DeRidderDenHertogException
{
    final public function __construct(public string $error)
    {
        parent::__construct($error);
    }
}
