<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\Core\Http\Soap;

use DeRidderDenHertog\Core\Failure\CouldNotCompleteRequest;
use DeRidderDenHertog\Core\Http\JsonMessage;
use DeRidderDenHertog\Core\Http\Soap\Envelope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[Group('core')]
final class EnvelopeTest extends TestCase
{
    /**
     * Verbatim response to an unpaginated GetDayTurnover that exceeded the server's own
     * request budget. Note the API answers it with a 200, not a 5xx.
     */
    private const string TIMEOUT_FAULT = <<<'XML'
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
        <SOAP-ENV:Body>
        <SOAP-ENV:Fault>
        <faultcode>SOAP-ENV:Server</faultcode>
        <faultstring>Request timeout (90 seconds).
        (22, ERR_TIMEOUT_REQUEST)</faultstring>
        </SOAP-ENV:Fault>
        </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        XML;

    #[Test]
    public function unwraps_a_result(): void
    {
        // Arrange
        $xml = $this->envelope(JsonMessage::encode(['Status' => 'Ok', 'Records' => ['Kassabonnen' => []]]));

        // Act
        $result = Envelope::unwrap($xml);

        // Assert
        $this->assertSame(['Status' => 'Ok', 'Records' => ['Kassabonnen' => []]], $result);
    }

    /** The documented failure mode: this used to leak a RuntimeException from the XML reader. */
    #[Test]
    public function throws_when_the_server_raises_a_timeout_fault(): void
    {
        // Assert
        $this->expectException(CouldNotCompleteRequest::class);
        $this->expectExceptionMessage('Request timeout (90 seconds).');

        // Act
        Envelope::unwrap(self::TIMEOUT_FAULT);
    }

    #[Test]
    public function it_exposes_the_fault(): void
    {
        // Act
        try {
            Envelope::unwrap(self::TIMEOUT_FAULT);
        } catch (CouldNotCompleteRequest $ex) {
            // Assert
            $this->assertStringContainsString('ERR_TIMEOUT_REQUEST', $ex->fault);

            return;
        }

        $this->fail('Expected a ' . CouldNotCompleteRequest::class . ' to be thrown.');
    }

    #[Test]
    public function throws_when_the_server_raises_any_other_fault(): void
    {
        // Assert
        $this->expectException(CouldNotCompleteRequest::class);
        $this->expectExceptionMessage('Unexpected error.');

        // Arrange
        $xml = <<<'XML'
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
            <SOAP-ENV:Body><SOAP-ENV:Fault>
            <faultcode>SOAP-ENV:Server</faultcode><faultstring>Unexpected error.</faultstring>
            </SOAP-ENV:Fault></SOAP-ENV:Body>
            </SOAP-ENV:Envelope>
            XML;

        // Act
        Envelope::unwrap($xml);
    }

    #[Test]
    public function throws_when_the_envelope_holds_neither_a_result_nor_a_fault(): void
    {
        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Element [RHDataServiceResult] not found in XML.');

        // Act
        Envelope::unwrap('<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body/></SOAP-ENV:Envelope>');
    }

    private function envelope(string $result): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Header/><SOAP-ENV:Body><RHDataServiceResult>{$result}</RHDataServiceResult></SOAP-ENV:Body></SOAP-ENV:Envelope>
            XML;
    }
}
