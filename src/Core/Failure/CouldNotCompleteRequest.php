<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Failure;

final class CouldNotCompleteRequest extends DeRidderDenHertogException
{
    public function __construct(public string $fault)
    {
        parent::__construct($fault);
    }

    public static function becauseTheServerRaisedAFault(string $fault): self
    {
        return new self($fault);
    }
}
