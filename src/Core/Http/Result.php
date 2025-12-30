<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Http;

/** @internal */
final readonly class Result
{
    public function __construct(
        public bool $ok,
        public string $answer,
        public string $error,
        public array $records,
        public array $raw,
    ) {}
}
