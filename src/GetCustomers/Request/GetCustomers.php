<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetCustomers\Request;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\Soap\Envelope;
use DeRidderDenHertog\Core\Http\Soap\Request;
use DeRidderDenHertog\GetCustomers\Fields;
use DeRidderDenHertog\GetCustomers\Filter;
use DeRidderDenHertog\GetCustomers\FromDate;

/** @internal */
final class GetCustomers extends Request
{
    public function __construct(
        private readonly ApiGuid $guid,
        private readonly ?Fields $fields = null,
        private readonly ?Filter $filter = null,
        private readonly ?FromDate $from = null,
    ) {}

    protected function defaultBody(): string
    {
        $message = array_filter([
            'APIGuid' => $this->guid->toMessageString(),
            'Action' => 'GetCustomers',
            'Fields' => $this->fields?->toMessageString(),
            'Filter' => $this->filter?->toMessageString(),
            'FromDate' => $this->from?->toMessageString(),
        ]);

        return Envelope::wrap($message);
    }
}
