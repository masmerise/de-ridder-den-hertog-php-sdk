<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Type\Primitive;

use Webmozart\Assert\Assert;

final readonly class CustomerId
{
    private function __construct(private int $id)
    {
        Assert::greaterThan($id, 0, 'The customer ID must be greater than 0.');
    }

    public static function fromInteger(int $id): self
    {
        return new self($id);
    }

    public function toInteger(): int
    {
        return $this->id;
    }

    public function toMessageArray(): array
    {
        return ['CustomerID' => $this->id];
    }
}
