<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use DeRidderDenHertog\Core\Http\JsonMessage;
use Saloon\XmlWrangler\Data\RootElement;
use Saloon\XmlWrangler\XmlReader;
use Saloon\XmlWrangler\XmlWriter;

/** @internal */
final readonly class Envelope
{
    public static function unwrap(XmlReader $xml): array
    {
        $result = $xml->value('RHDataServiceResult')->sole();

        return JsonMessage::decode($result);
    }

    public static function wrap(array $message): string
    {
        $root = RootElement::make('soapenv:Envelope')->addNamespace('soapenv', 'https://schemas.xmlsoap.org/soap/envelope');

        return XmlWriter::make()->write($root, [
            'soapenv:Body' => ['paramRequest' => JsonMessage::encode($message)],
        ]);
    }
}
