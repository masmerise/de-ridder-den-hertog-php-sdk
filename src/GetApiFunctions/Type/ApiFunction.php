<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetApiFunctions\Type;

final readonly class ApiFunction
{
    public function __construct(
        public string $description,
        public string $function,
        public string $remarks,
        public string $example,
    ) {}
}
