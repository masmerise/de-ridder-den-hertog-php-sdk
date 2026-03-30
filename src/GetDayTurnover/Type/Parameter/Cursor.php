<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Type\Parameter;

use DeRidderDenHertog\Core\Type\Parameter\PerPage;
use Webmozart\Assert\Assert;

final readonly class Cursor
{
    private function __construct(
        private int $position,
        private bool $hasMore,
    ) {
        Assert::greaterThanEq($position, 0, 'The cursor position must be >= 0.');
    }

    public static function start(): self
    {
        return new self(0, true);
    }

    public static function define(int $position, int $nbRecords, PerPage $perPage): self
    {
        return new self($position, $nbRecords >= $perPage->toInteger());
    }

    public function toInteger(): int
    {
        return $this->position;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }
}
