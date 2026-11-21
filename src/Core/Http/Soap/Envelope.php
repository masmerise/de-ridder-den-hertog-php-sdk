<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use DeRidderDenHertog\Core\Failure\CouldNotCompleteRequest;
use DeRidderDenHertog\Core\Http\JsonMessage;
use DeRidderDenHertog\Core\Xml\Reader;
use DeRidderDenHertog\Core\Xml\Writer;

/** @internal */
final readonly class Envelope
{
    /** @throws CouldNotCompleteRequest */
    public static function unwrap(string $xml): array
    {
        $reader = Reader::fromString($xml);

        // The API answers faults with a 200, so the envelope is the only place they surface.
        if (($fault = $reader->find('faultstring')) !== null) {
            throw CouldNotCompleteRequest::becauseTheServerRaisedAFault($fault);
        }

        return JsonMessage::decode($reader->value('RHDataServiceResult'));
    }

    public static function wrap(array $message): string
    {
        return Writer::write('soapenv:Envelope', ['soapenv' => 'https://schemas.xmlsoap.org/soap/envelope'], [
            'soapenv:Body' => ['paramRequest' => JsonMessage::encode($message)],
        ]);
    }
}
