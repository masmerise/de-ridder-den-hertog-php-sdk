<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http;

use DeRidderDenHertog\Environments\Environment;
use Saloon\Http\Connector;

/** @internal */
final class DeRidderDenHertogConnector extends Connector
{
    private Environment $environment = Environment::Production;

    public function resolveBaseUrl(): string
    {
        return $this->environment->url();
    }

    public function useEnvironment(Environment $environment): void
    {
        $this->environment = $environment;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/xml',
            'SOAPAction' => 'urn:RHAPI/RHDataService',
        ];
    }
}
