<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http;

use DeRidderDenHertog\Core\Http\Soap\Request as SoapRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Paginator;

abstract class CursorPaginator extends Paginator
{
    protected ?int $perPageLimit = 100;

    /** @param SoapRequest $request */
    protected function applyPagination(Request $request): Request
    {

    }

    protected function isLastPage(Response $response): bool
    {
        $response = XmlResponse::decode($response);

        $lastRecord = $response['Lastrecord'] ?? 0;

        return $lastRecord === 0;
    }
}
