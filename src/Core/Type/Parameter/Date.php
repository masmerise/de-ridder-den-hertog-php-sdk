<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Type\Parameter;

use DateTimeImmutable;
use DateTimeInterface;
use Webmozart\Assert\Assert;

final readonly class Date
{
    private function __construct(private string $date) {}

    public static function fromDateString(string $date): self
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        Assert::isInstanceOf($parsed, DateTimeImmutable::class, "{$date} could not be parsed as a valid date. It must be in the format YYYY-MM-DD.");

        return new self($date);
    }

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        return new self($dateTime->format('Y-m-d'));
    }

    public function toMessageString(): string
    {
        return str_replace('-', '', $this->date);
    }
}
