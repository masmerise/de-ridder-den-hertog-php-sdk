<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type;

final readonly class PayForm
{
    public function __construct(
        public float $amount,
        public int $id,
        public string $name,
    ) {}
}