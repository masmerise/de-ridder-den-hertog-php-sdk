<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Pagination;

use DeRidderDenHertog\Core\Http\Request as SoapRequest;
use DeRidderDenHertog\Core\Http\Result;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Paginator;

final class CursorPaginator extends Paginator
{
    protected ?int $perPageLimit = 100;

    /** @param SoapRequest $request */
    protected function applyPagination(Request $request): Request
    {
        $request->body()->add('RequestCount', $this->perPageLimit);

        if ($this->currentResponse instanceof Response) {
            $request->body()->add('LastRecord', $this->getNextCursor($this->currentResponse));
        }

        return $request;
    }

    protected function getPageItems(Response $response, Request $request): array
    {
        /** @var Result $result */
        $result = $response->dto();

        if (! array_key_exists('Kassabonnen', $result->records)) {
            return [];
        }

        return $result->records['Kassabonnen'];
    }

    protected function getNextCursor(Response $response): int
    {
        /** @var Result $result */
        $result = $response->dto();

        return $result->raw['Lastrecord'];
    }

    protected function isLastPage(Response $response): bool
    {
        /** @var Result $result */
        $result = $response->dto();

        $lastRecord = $result->raw['Lastrecord'] ?? 0;

        return $lastRecord === 0;
    }
}
