<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\GetDayTurnover;

use DeRidderDenHertog\Core\Type\Parameter\PerPage;
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
        $cursor = Cursor::start();

        $this->assertInstanceOf(Cursor::class, $cursor);
        $this->assertSame(0, $cursor->toInteger());
        $this->assertTrue($cursor->hasMore());
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith([0, 10, 10, true])]
    #[TestWith([5, 10, 10, true])]
    #[TestWith([42, 5, 10, false])]
    #[TestWith([100, 0, 10, false])]
    public function define_with_valid_values(int $position, int $nbRecords, int $perPage, bool $expectedHasMore): void
    {
        $cursor = Cursor::define($position, $nbRecords, PerPage::count($perPage));

        $this->assertInstanceOf(Cursor::class, $cursor);
        $this->assertSame($position, $cursor->toInteger());
        $this->assertSame($expectedHasMore, $cursor->hasMore());
    }

    #[Test]
    #[Group('get-day-turnover')]
    #[TestWith([-1])]
    #[TestWith([-5])]
    public function define_rejects_negative_position(int $position): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cursor position must be >= 0/');

        Cursor::define($position, 10, PerPage::count(10));
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function has_more_when_nb_records_equals_per_page(): void
    {
        $cursor = Cursor::define(10, 25, PerPage::count(25));

        $this->assertTrue($cursor->hasMore());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function has_no_more_when_nb_records_less_than_per_page(): void
    {
        $cursor = Cursor::define(10, 15, PerPage::count(25));

        $this->assertFalse($cursor->hasMore());
    }
}
