<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type;

use DeRidderDenHertog\GetDayTurnover\Type\Parameter\Cursor;

final readonly class DayTurnoverPage
{
    private function __construct(
        public Transactions $transactions,
        public Cursor $cursor,
    ) {}

    public static function of(Transactions $transactions, Cursor $cursor): self
    {
        return new self($transactions, $cursor);
    }
}
