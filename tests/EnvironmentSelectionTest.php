<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests;

use DeRidderDenHertog\Authentication\ApiGuid;
use DeRidderDenHertog\Core\Http\DeRidderDenHertogConnector;
use DeRidderDenHertog\DeRidderDenHertog;
use DeRidderDenHertog\Environments\Environment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[Group('environments')]
final class EnvironmentSelectionTest extends TestCase
{
    #[Test]
    public function it_targets_production_by_default(): void
    {
        // Arrange
        $renh = $this->newInstance();

        // Act & Assert
        $this->assertSame(Environment::Production->url(), $this->baseUrlOf($renh));
    }

    #[Test]
    public function it_can_target_the_test_environment(): void
    {
        // Arrange
        $renh = $this->newInstance();

        // Act
        $result = $renh->useTest();

        // Assert
        $this->assertSame($renh, $result);
        $this->assertSame(Environment::Test->url(), $this->baseUrlOf($renh));
    }

    #[Test]
    public function it_can_target_the_production_environment(): void
    {
        // Arrange
        $renh = $this->newInstance()->useTest();

        // Act
        $result = $renh->useProduction();

        // Assert
        $this->assertSame($renh, $result);
        $this->assertSame(Environment::Production->url(), $this->baseUrlOf($renh));
    }

    private function baseUrlOf(DeRidderDenHertog $renh): string
    {
        $client = new ReflectionProperty(DeRidderDenHertog::class, 'client')->getValue($renh);

        $this->assertInstanceOf(DeRidderDenHertogConnector::class, $client);

        return $client->resolveBaseUrl();
    }

    private function newInstance(): DeRidderDenHertog
    {
        return DeRidderDenHertog::authenticate(
            ApiGuid::fromString('{4844a45c-33d1-4937-83f4-366d36449eaf}')
        );
    }
}
