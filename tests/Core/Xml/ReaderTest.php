<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\Core\Xml;

use DeRidderDenHertog\Core\Xml\Reader;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[Group('core')]
final class ReaderTest extends TestCase
{
    #[Test]
    public function finds_an_element(): void
    {
        // Act & Assert
        $this->assertSame('bar', Reader::fromString('<root><foo>bar</foo></root>')->find('foo'));
    }

    #[Test]
    public function finds_nothing_when_the_element_is_absent(): void
    {
        // Act & Assert
        $this->assertNull(Reader::fromString('<root><foo>bar</foo></root>')->find('baz'));
    }

    #[Test]
    public function reads_a_value(): void
    {
        // Act & Assert
        $this->assertSame('bar', Reader::fromString('<root><foo>bar</foo></root>')->value('foo'));
    }

    #[Test]
    public function throws_when_the_value_is_absent(): void
    {
        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Element [baz] not found in XML.');

        // Act
        Reader::fromString('<root><foo>bar</foo></root>')->value('baz');
    }

    #[Test]
    public function throws_when_the_value_is_ambiguous(): void
    {
        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected exactly one [foo] element, found 2.');

        // Act
        Reader::fromString('<root><foo>bar</foo><foo>baz</foo></root>')->value('foo');
    }
}
