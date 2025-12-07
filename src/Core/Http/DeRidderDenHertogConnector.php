<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http;

use Saloon\Http\Connector;

/** @internal */
final class DeRidderDenHertogConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://renh.online/RHAPI_WEB/awws/RHAPI.awws';
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/xml',
            'SOAPAction' => 'urn:RHAPI/RHDataService',
        ];
    }
}
