<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http;

use DeRidderDenHertog\Core\Http\Soap\JsonMessage;
use JsonException;
use Saloon\Http\Response;

/** @internal */
final readonly class XmlResponse
{
    /** @throws JsonException */
    public static function decode(Response $response): array
    {
        $result = $response->xmlReader()->value('RHDataServiceResult')->sole();

        return JsonMessage::decode($result);
    }
}
