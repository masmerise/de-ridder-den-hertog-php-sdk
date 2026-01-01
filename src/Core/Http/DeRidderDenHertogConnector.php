<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http;

use DeRidderDenHertog\Core\Http\Pagination\CursorPaginator;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\HasPagination;

/** @internal */
final class DeRidderDenHertogConnector extends Connector implements HasPagination
{
    public function paginate(Request $request): CursorPaginator
    {
        return new CursorPaginator($this, $request);
    }

    public function resolveBaseUrl(): string
    {
        return 'https://renh.online/RHAPI_WEB/awws/RHAPI.awws';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/xml',
            'SOAPAction' => 'urn:RHAPI/RHDataService',
        ];
    }
}
