<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use Saloon\XmlWrangler\Data\RootElement;
use Saloon\XmlWrangler\XmlWriter;

/** @internal */
final readonly class Envelope
{
    public static function wrap(array $message): string
    {
        $root = RootElement::make('soapenv:Envelope')->addNamespace('soapenv', 'https://schemas.xmlsoap.org/soap/envelope');

        return XmlWriter::make()->write($root, [
            'soapenv:Body' => ['paramRequest' => Message::encode($message)],
        ]);
    }
}
