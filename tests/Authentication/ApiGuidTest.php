<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests;

use DeRidderDenHertog\Authentication\ApiGuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class ApiGuidTest extends TestCase
{
    #[Test]
    #[TestWith(['{b28ee2a1-ef5d-46e9-a1bc-4922e0070fe0}'])]
    #[TestWith(['{3d77b9b7-9615-471b-a0ae-6dd05258d51a}'])]
    #[TestWith(['{91cd031f-a761-4029-b705-3ef1034cfb14}'])]
    #[TestWith(['{91CD031F-A761-4029-B705-3EF1034CFB14}'])]
    #[TestWith(['{6ba7b810-9dad-11d1-80b4-00c04fd430c8}'])]
    public function valid(string $guid): void
    {
        $this->assertInstanceOf(ApiGuid::class, ApiGuid::fromString($guid));
    }

    #[Test]
    #[TestWith(['{}'])]
    #[TestWith(['b28ee2a1-ef5d-46e9-a1bc-4922e0070fe0'])]
    #[TestWith(['{b28ee2a1-ef5d-46e9-a1bc-4922e0070fe0'])]
    #[TestWith(['b28ee2a1-ef5d-46e9-a1bc-4922e0070fe0}'])]
    #[TestWith(['{g28ee2a1-ef5d-46e9-a1bc-4922e0070fe0}'])]
    #[TestWith(['{b28ee2a1-ef5d-46e9-a1bc-4922e0070fe}'])]
    public function invalid(string $guid): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database GUID is not valid!');

        ApiGuid::fromString($guid);
    }
}
