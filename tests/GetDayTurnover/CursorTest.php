<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\GetDayTurnover;

use DeRidderDenHertog\GetDayTurnover\Pagination\Cursor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class CursorTest extends TestCase
{
    #[Test]
    #[Group('get-day-turnover')]
    public function start_creates_cursor_at_position_zero(): void
    {
        $cursor = Cursor::start();

        $this->assertSame(0, $cursor->toInteger());
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith([0])]
    #[TestWith([1])]
    #[TestWith([42])]
    #[TestWith([100])]
    public function at_creates_cursor_at_given_position(int $position): void
    {
        $cursor = Cursor::at($position);

        $this->assertSame($position, $cursor->toInteger());
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith([-1])]
    #[TestWith([-5])]
    public function rejects_negative_position(int $position): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cursor position must be >= 0/');

        Cursor::at($position);
    }
}
