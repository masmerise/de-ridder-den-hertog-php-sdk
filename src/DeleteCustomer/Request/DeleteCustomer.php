<?php declare(strict_types=1);

namespace DeRidderDenHertog\DeleteCustomer\Request;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\Soap\Envelope;
use DeRidderDenHertog\Core\Http\Soap\Request;
use DeRidderDenHertog\Core\Type\Primitive\CustomerId;

/** @internal */
final class DeleteCustomer extends Request
{
    public function __construct(
        private readonly ApiGuid $guid,
        private readonly CustomerId $id,
    ) {}

    protected function defaultBody(): string
    {
        return Envelope::wrap([
            'APIGuid' => $this->guid->toMessageString(),
            'Action' => 'DeleteCustomer',
            'Customer' => $this->id->toMessageArray(),
        ]);
    }
}
