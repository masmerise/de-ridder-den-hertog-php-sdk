<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Request;

use DeRidderDenHertog\Core\Http\Request;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use Saloon\Traits\Plugins\HasTimeout;

/** @internal */
final class GetDayTurnover extends Request implements Paginatable
{
    use HasTimeout;

    protected string $action = 'GetDayTurnover';

    protected int $requestTimeout = 120;

    public function __construct(
        private readonly ?Filter $filter = null,
        private readonly ?Date $from = null,
        private readonly ?Date $till = null,
    ) {}

    protected function message(): array
    {
        $message = array_filter([
            'Filter' => $this->filter?->toMessageString(),
            'FromDate' => $this->from?->toMessageString(),
        ]);

        // Must always be set otherwise the API returns an empty response.
        $message['TillDate'] = $this->till?->toMessageString() ?? '';

        return $message;
    }
}
