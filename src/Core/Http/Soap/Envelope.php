<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use DeRidderDenHertog\Core\Http\JsonMessage;
use DeRidderDenHertog\Core\Xml\Reader;
use DeRidderDenHertog\Core\Xml\Writer;

/** @internal */
final readonly class Envelope
{
    public static function unwrap(string $xml): array
    {
        $result = Reader::fromString($xml)->value('RHDataServiceResult');

        return JsonMessage::decode($result);
    }

    public static function wrap(array $message): string
    {
        return Writer::write('soapenv:Envelope', ['soapenv' => 'https://schemas.xmlsoap.org/soap/envelope'], [
            'soapenv:Body' => ['paramRequest' => JsonMessage::encode($message)],
        ]);
    }
}
