<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http\Soap;

/** @internal */
final readonly class Response
{
    public function __construct(
        public bool $ok,
        public string $answer,
        public string $error,
        public array $records,
        public array $raw,
    ) {}
}
