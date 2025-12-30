<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetCustomers\Request;

use DeRidderDenHertog\Core\Http\Request;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;
use DeRidderDenHertog\GetCustomers\Type\Parameter\Fields;

/** @internal */
final class GetCustomers extends Request
{
    protected string $action = 'GetCustomers';

    public function __construct(
        private readonly ?Fields $fields = null,
        private readonly ?Filter $filter = null,
        private readonly ?Date $from = null,
    ) {}

    protected function message(): array
    {
        return [
            'Fields' => $this->fields?->toMessageString(),
            'Filter' => $this->filter?->toMessageString(),
            'FromDate' => $this->from?->toMessageString(),
        ];
    }
}
