<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Type\Parameter;

use Webmozart\Assert\Assert;

final readonly class PerPage
{
    public function __construct(private int $count)
    {
        Assert::greaterThan($count, 0, 'The per page count must be greater than 0.');
    }

    public static function count(int $count): self
    {
        return new self($count);
    }

    public function toInteger(): int
    {
        return $this->count;
    }
}
