<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Xml;

use Exception;
use RuntimeException;
use SimpleXMLElement;

/** @internal */
final readonly class Reader
{
    private function __construct(private SimpleXMLElement $xml) {}

    /** @throws Exception */
    public static function fromString(string $xml): self
    {
        return new self(new SimpleXMLElement($xml));
    }

    public function find(string $element): ?string
    {
        $results = $this->lookup($element);

        return $results === [] ? null : (string) $results[0];
    }

    public function value(string $element): string
    {
        $results = $this->lookup($element);

        return match (count($results)) {
            0 => throw new RuntimeException("Element [{$element}] not found in XML."),
            1 => (string) $results[0],
            default => throw new RuntimeException("Expected exactly one [{$element}] element, found " . count($results) . '.'),
        };
    }

    /** @return list<SimpleXMLElement> */
    private function lookup(string $element): array
    {
        $this->xml->registerXPathNamespace('soap', 'https://schemas.xmlsoap.org/soap/envelope');

        return array_values($this->xml->xpath("//{$element}") ?: []);
    }
}
