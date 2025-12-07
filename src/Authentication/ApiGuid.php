<?php declare(strict_types=1);

namespace DeRidderDenHertog\Authentication;

use SensitiveParameter;
use Webmozart\Assert\Assert;

final readonly class ApiGuid
{
    private function __construct(#[SensitiveParameter] private string $guid)
    {
        Assert::regex($guid, '/^\{[0-9a-f]{8}-[0-9a-f]{4}-[4][0-9a-f]{3}-[89aB][0-9a-f]{3}-[0-9a-f]{12}\}$/', 'Database GUID is not valid!');
    }

    public static function fromString(string $guid): self
    {
        return new self($guid);
    }

    public function toMessageString(): string
    {
        return $this->guid;
    }
}
