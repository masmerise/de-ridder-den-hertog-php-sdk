<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\Environments;

use DeRidderDenHertog\Environments\Environment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('environments')]
final class EnvironmentTest extends TestCase
{
    #[Test]
    public function production_url(): void
    {
        // Act
        $url = Environment::Production->url();

        // Assert
        $this->assertSame('https://renh.online/RHAPI_WEB/awws/RHAPI.awws', $url);
    }

    #[Test]
    public function test_url(): void
    {
        // Act
        $url = Environment::Test->url();

        // Assert
        $this->assertSame('https://test.renh.online/rhapi_web/awws/rhapi.awws', $url);
    }
}
