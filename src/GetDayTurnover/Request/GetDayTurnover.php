<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Request;

use DeRidderDenHertog\Core\Http\Request;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;

/** @internal */
final class GetDayTurnover extends Request
{
    protected string $action = 'GetDayTurnover';

    public function __construct(
        private readonly ?Filter $filter = null,
        private readonly ?Date $from = null,
        private readonly ?Date $till = null,
    ) {}

    protected function message(): array
    {
        return [
            'Filter' => $this->filter?->toMessageString(),
            'FromDate' => $this->from?->toMessageString(),
            'TillDate' => $this->till?->toMessageString(),
        ];
    }
}
