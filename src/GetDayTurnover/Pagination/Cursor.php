<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Pagination;

use Webmozart\Assert\Assert;

final readonly class Cursor
{
    private function __construct(private int $position)
    {
        Assert::greaterThanEq($position, 0, 'The cursor position must be >= 0.');
    }

    public static function at(int $position): self
    {
        return new self($position);
    }

    public static function start(): self
    {
        return new self(0);
    }

    public function hasAdvanced(): bool
    {
        return $this->position > 0;
    }

    public function toInteger(): int
    {
        return $this->position;
    }
}
