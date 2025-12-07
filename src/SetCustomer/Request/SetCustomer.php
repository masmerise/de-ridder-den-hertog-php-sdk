<?php declare(strict_types=1);

namespace DeRidderDenHertog\SetCustomer\Request;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\Soap\Envelope;
use DeRidderDenHertog\Core\Http\Soap\Request;
use DeRidderDenHertog\SetCustomer\Type\Parameter\CustomerData;

/** @internal */
final class SetCustomer extends Request
{
    public function __construct(
        private readonly ApiGuid $guid,
        private readonly CustomerData $data,
    ) {}

    protected function defaultBody(): string
    {
        return Envelope::wrap([
            'APIGuid' => $this->guid->toMessageString(),
            'Action' => 'SetCustomer',
            'Customer' => $this->data->toMessageArray(),
        ]);
    }
}
