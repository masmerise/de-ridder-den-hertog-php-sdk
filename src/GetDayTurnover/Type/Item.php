<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type;

final readonly class Item
{
    public function __construct(
        public int $count,
        public float $gewicht,
        public int $plu,
        public float $price,
        public float $brutoverkoopwaarde,
        public float $korting,
        public float $verkoopwaarde,
        public float $inkoopwaarde,
        public string $scancode,
        public int $subgroep,
        public int $tax,
        public int $taxperc,
        public int $hoofdgroep,
        public int $afdeling,
        public int $omzetsoort,
        public bool $gewichtartikel,
        public string $text,
        public string $actieomzet,
    ) {}
}