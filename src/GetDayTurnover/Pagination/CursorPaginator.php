<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Pagination;

use DeRidderDenHertog\Core\Type\Parameter\PerPage;

final readonly class CursorPaginator
{
    private function __construct(
        public Cursor $cursor,
        private int $count,
        private PerPage $perPage,
    ) {}

    public static function of(Cursor $cursor, int $count, PerPage $perPage): self
    {
        return new self($cursor, $count, $perPage);
    }

    public function hasMore(): bool
    {
        return $this->count >= $this->perPage->toInteger();
    }

    public function onFirstPage(): bool
    {
        return $this->cursor->toInteger() === 0
            || $this->cursor->toInteger() <= $this->perPage->toInteger();
    }

    public function onLastPage(): bool
    {
        return ! $this->hasMore();
    }
}
