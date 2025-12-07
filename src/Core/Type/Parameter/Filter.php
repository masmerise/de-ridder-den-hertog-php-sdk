<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Type\Parameter;

use Webmozart\Assert\Assert;

final readonly class Filter
{
    private function __construct(private string $filter)
    {
        Assert::stringNotEmpty($filter, 'The filter must not be empty.');
    }

    public static function fromSql(string $filter): self
    {
        return new self($filter);
    }

    public function toMessageString(): string
    {
        return $this->filter;
    }
}
