<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type;

use DateTimeImmutable;

final readonly class Transaction
{
    public function __construct(
        public int $branch,
        public int $register,
        public string $billno,
        public int $cashier,
        public ?int $custno,
        public ?string $custname,
        public DateTimeImmutable $time,
        public float $total,
        public ?string $vatNumber,
        public bool $doNotChargeVat,
        /** @var Item[] */
        public array $ordered,
        /** @var PayForm[] */
        public array $payforms,
    ) {}
}