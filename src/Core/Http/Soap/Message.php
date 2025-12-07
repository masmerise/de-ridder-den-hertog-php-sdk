<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

use JsonException;

/** @internal */
final readonly class Message
{
    /** @throws JsonException */
    public static function decode(string $request): array
    {
        return json_decode(base64_decode($request), true, JSON_THROW_ON_ERROR);
    }

    public static function encode(array $request): string
    {
        return base64_encode(json_encode($request));
    }
}
