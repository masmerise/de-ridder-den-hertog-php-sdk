<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetCustomers\Request;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\Soap\Envelope;
use DeRidderDenHertog\Core\Http\Soap\Request;
use DeRidderDenHertog\Core\Type\Parameter\Date;
use DeRidderDenHertog\Core\Type\Parameter\Filter;
use DeRidderDenHertog\GetCustomers\Type\Parameter\Fields;

/** @internal */
final class GetCustomers extends Request
{
    public function __construct(
        private readonly ApiGuid $guid,
        private readonly ?Fields $fields = null,
        private readonly ?Filter $filter = null,
        private readonly ?Date $from = null,
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
