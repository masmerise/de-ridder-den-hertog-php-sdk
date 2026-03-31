<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type;

use DeRidderDenHertog\GetDayTurnover\Pagination\CursorPaginator;

final readonly class DayTurnoverPage
{
    private function __construct(
        public Transactions $transactions,
        public CursorPaginator $paginator,
    ) {}

    public static function of(Transactions $transactions, CursorPaginator $paginator): self
    {
        return new self($transactions, $paginator);
    }
}
