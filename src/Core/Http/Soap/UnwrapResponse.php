<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use Saloon\Http\Response;

final readonly class UnwrapResponse
{
    public function __invoke(Response $response): array
    {
        return $response->xmlReader() |> Envelope::unwrap(...);
    }
}
