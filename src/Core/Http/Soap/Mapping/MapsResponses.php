<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap\Mapping;

use DeRidderDenHertog\Core\Http\Soap\Response;

/** @internal */
trait MapsResponses
{
    /**
     * @param array{
     *     Status: 'Ok'|'Not Ok',
     *     Answer: string,
     *     ErrorString: string,
     *     Records?: array,
     * } $response
     */
    private function toResponse(array $response): Response
    {
        return new Response(
            ok: $response['Status'] === 'Ok',
            answer: $response['Answer'],
            error: $response['ErrorString'],
            records: $response['Records'] ?? [],
            raw: $response,
        );
    }
}
