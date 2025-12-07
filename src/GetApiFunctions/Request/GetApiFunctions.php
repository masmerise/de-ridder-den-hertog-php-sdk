<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetApiFunctions\Request;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\Soap\Envelope;
use DeRidderDenHertog\Core\Http\Soap\Request;

/** @internal */
final class GetApiFunctions extends Request
{
    public function __construct(private readonly ApiGuid $guid) {}

    protected function defaultBody(): string
    {
        return Envelope::wrap([
            'APIGuid' => $this->guid->toMessageString(),
            'Action' => 'GetAPIFunctions',
        ]);
    }
}
