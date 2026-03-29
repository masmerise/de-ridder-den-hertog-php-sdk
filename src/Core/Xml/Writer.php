<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Xml;

use XMLWriter;

/** @internal */
final readonly class Writer
{
    /**
     * @param  array<string, string>  $namespaces
     * @param  array<string, mixed>  $content
     */
    public static function write(string $rootElement, array $namespaces, array $content): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');

        $writer->startElement($rootElement);

        foreach ($namespaces as $prefix => $uri) {
            $writer->writeAttribute("xmlns:{$prefix}", $uri);
        }

        self::writeContent($writer, $content);

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private static function writeContent(XMLWriter $writer, array $content): void
    {
        foreach ($content as $key => $value) {
            $writer->startElement($key);

            if (is_array($value)) {
                self::writeContent($writer, $value);
            } else {
                $writer->text((string) $value);
            }

            $writer->endElement();
        }
    }
}
