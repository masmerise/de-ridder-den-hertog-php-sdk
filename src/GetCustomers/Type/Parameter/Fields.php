<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetCustomers\Type\Parameter;

use DeRidderDenHertog\Core\Type\Collection;
use Webmozart\Assert\Assert;

/** @extends Collection<Field> */
final readonly class Fields extends Collection
{
    /** @param Field[] $items */
    protected function __construct(array $items = [])
    {
        Assert::minCount($items, 1, 'At least one field must be specified.');

        parent::__construct($items);
    }

    public function toMessageString(): string
    {
        $fields = array_map(static fn (Field $field) => $field->value, $this->items);

        return implode(';', $fields);
    }
}
