<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetDayTurnover\Request;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\Soap\Envelope;
use DeRidderDenHertog\Core\Http\Soap\Request;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;

/** @internal */
final class GetDayTurnover extends Request
{
    public function __construct(
        private readonly ApiGuid $guid,
        private readonly ?Filter $filter = null,
        private readonly ?Date $from = null,
        private readonly ?Date $till = null,
    ) {}

    protected function defaultBody(): string
    {
        $message = array_filter([
            'APIGuid' => $this->guid->toMessageString(),
            'Action' => 'GetDayTurnover',
            'Filter' => $this->filter?->toMessageString(),
            'FromDate' => $this->from?->toMessageString(),
            'LastRecord' => null, // TODO — Pagination
            'RequestCount' => null, // TODO — Pagination
            'TillDate' => $this->till?->toMessageString(),
        ]);

        return Envelope::wrap($message);
    }
}
