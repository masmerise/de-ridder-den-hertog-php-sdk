<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\GetDayTurnover;

use DeRidderDenHertog\GetDayTurnover\Type\Parameter\Cursor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class CursorTest extends TestCase
{
    #[Test]
    #[Group('get-day-turnover')]
    public function start_creates_cursor_at_position_zero_with_has_more_true(): void
    {
        // Arrange & Act
        $cursor = Cursor::start();

        // Assert
        $this->assertInstanceOf(Cursor::class, $cursor);
        $this->assertSame(0, $cursor->toInteger());
        $this->assertTrue($cursor->hasMore());
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith([0, true])]
    #[TestWith([1, true])]
    #[TestWith([42, false])]
    #[TestWith([100, false])]
    public function from_integer_with_valid_values(int $position, bool $hasMore): void
    {
        // Arrange & Act
        $cursor = Cursor::fromInteger($position, $hasMore);

        // Assert
        $this->assertInstanceOf(Cursor::class, $cursor);
        $this->assertSame($position, $cursor->toInteger());
        $this->assertSame($hasMore, $cursor->hasMore());
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith([-1, true])]
    #[TestWith([-5, false])]
    public function from_integer_rejects_negative_values(int $position, bool $hasMore): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cursor position must be >= 0/');

        Cursor::fromInteger($position, $hasMore);
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function has_more_returns_true_when_set(): void
    {
        // Arrange
        $cursor = Cursor::fromInteger(10, true);

        // Act & Assert
        $this->assertTrue($cursor->hasMore());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function has_more_returns_false_when_set(): void
    {
        // Arrange
        $cursor = Cursor::fromInteger(10, false);

        // Act & Assert
        $this->assertFalse($cursor->hasMore());
    }
}
