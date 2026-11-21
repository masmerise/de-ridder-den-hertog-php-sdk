<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\Core\Http;

use DeRidderDenHertog\Core\Http\DeRidderDenHertogConnector;
use DeRidderDenHertog\Environments\Environment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('environments')]
final class DeRidderDenHertogConnectorTest extends TestCase
{
    #[Test]
    public function it_defaults_to_production(): void
    {
        // Arrange
        $connector = new DeRidderDenHertogConnector();

        // Act & Assert
        $this->assertSame(Environment::Production->url(), $connector->resolveBaseUrl());
    }

    #[Test]
    public function it_resolves_the_base_url_of_the_selected_environment(): void
    {
        // Arrange
        $connector = new DeRidderDenHertogConnector();

        // Act
        $connector->useEnvironment(Environment::Test);

        // Assert
        $this->assertSame(Environment::Test->url(), $connector->resolveBaseUrl());

        // Act
        $connector->useEnvironment(Environment::Production);

        // Assert
        $this->assertSame(Environment::Production->url(), $connector->resolveBaseUrl());
    }
}
