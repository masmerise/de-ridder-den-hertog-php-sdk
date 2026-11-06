<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\Core\Http;

use DeRidderDenHertog\Core\Http\JsonMessage;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JsonMessageTest extends TestCase
{
    #[Test]
    #[Group('core')]
    public function decodes_a_base64_encoded_message(): void
    {
        $message = JsonMessage::decode(base64_encode('{"Status":"Ok","Records":[]}'));

        $this->assertSame(['Status' => 'Ok', 'Records' => []], $message);
    }

    #[Test]
    #[Group('core')]
    public function round_trips_a_message(): void
    {
        $message = ['TillDate' => '2026-11-06', 'RequestCount' => 50];

        $this->assertSame($message, JsonMessage::decode(JsonMessage::encode($message)));
    }

    /** The documented failure mode: before the flag reached $flags, this returned null instead. */
    #[Test]
    #[Group('core')]
    public function throws_when_the_payload_is_not_valid_json(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        JsonMessage::decode(base64_encode('{"Status":'));
    }
}
